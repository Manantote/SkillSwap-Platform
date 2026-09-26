<?php
/**
 * generate_quiz.php
 * POST — Check eligibility and return 10 randomised MCQ questions for a skill.
 *
 * Required:
 *   X-Firebase-UID
 *
 * Body:
 *   skill_id  int
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'POST method required.', 405);
}

$uid  = requireUID();
$body = json_decode(file_get_contents('php://input'), true) ?? [];
$skillId = (int)($body['skill_id'] ?? $_POST['skill_id'] ?? 0);

if (!$skillId) {
    jsonResponse(false, 'skill_id is required.', 400);
}

$pdo    = getDB();
$userId = getUserIdByUID($pdo, $uid);
if (!$userId) jsonResponse(false, 'User not found.', 404);

// Fetch skill name
$sk = $pdo->prepare('SELECT skill_name FROM skills WHERE skill_id = ?');
$sk->execute([$skillId]);
$skillName = $sk->fetchColumn();
if (!$skillName) jsonResponse(false, 'Skill not found.', 404);

// ── Eligibility check ──────────────────────────────────────────
$prog = $pdo->prepare(
    'SELECT * FROM learning_progress WHERE user_id = ? AND skill_id = ? LIMIT 1'
);
$prog->execute([$userId, $skillId]);
$progress = $prog->fetch();

if (!$progress || $progress['sessions_completed'] < 3 || $progress['total_time'] < 60) {
    $need = [];
    if (!$progress || $progress['sessions_completed'] < 3) {
        $have = $progress ? $progress['sessions_completed'] : 0;
        $need[] = (3 - $have) . ' more session(s)';
    }
    if (!$progress || $progress['total_time'] < 60) {
        $have = $progress ? $progress['total_time'] : 0;
        $need[] = (60 - $have) . ' more minute(s) of learning';
    }
    jsonResponse(false, [
        'reason'   => 'Not eligible yet. You need: ' . implode(' and ', $need) . '.',
        'progress' => $progress ?: ['sessions_completed' => 0, 'total_time' => 0, 'status' => 'learning'],
    ], 403);
}

// ── Attempt spam guard — max 3 attempts per 24h ───────────────
$attemptCheck = $pdo->prepare(
    "SELECT COUNT(*) FROM quiz_attempts
     WHERE user_id = ? AND skill_id = ? AND created_at > NOW() - INTERVAL 24 HOUR"
);
$attemptCheck->execute([$userId, $skillId]);
if ((int)$attemptCheck->fetchColumn() >= 3) {
    jsonResponse(false, 'Maximum 3 quiz attempts per 24 hours. Please try again later.', 429);
}

// ── Generate questions ─────────────────────────────────────────
$questions = getQuestionsForSkill($skillName);
shuffle($questions);
$questions = array_slice($questions, 0, 10);

// Strip correct_index before sending to client (stored server-side in session or validated on submit)
// We send a quiz_token so submit can retrieve answers securely
$token = bin2hex(random_bytes(16));

// Store questions temporarily using a transient DB row in quiz_attempts (score=-1 = pending)
// We embed correct answers in a JSON field kept server-side
$pending = $pdo->prepare(
    'INSERT INTO quiz_attempts (user_id, skill_id, score, total_q, correct_q, passed, answers_json)
     VALUES (?, ?, -1, ?, 0, 0, ?)'
);
$correctAnswers = array_map(fn($q) => $q['correct_index'], $questions);
$pending->execute([$userId, $skillId, count($questions), json_encode([
    'token'    => $token,
    'answers'  => $correctAnswers,
])]);
$attemptId = (int)$pdo->lastInsertId();

// Strip correct_index from client payload
$clientQuestions = array_map(function ($q, $i) {
    return [
        'index'    => $i,
        'question' => $q['question'],
        'options'  => $q['options'],
    ];
}, $questions, array_keys($questions));

jsonResponse(true, [
    'attempt_id'  => $attemptId,
    'quiz_token'  => $token,
    'skill_id'    => $skillId,
    'skill_name'  => $skillName,
    'questions'   => $clientQuestions,
    'total'       => count($clientQuestions),
    'pass_score'  => 70,
]);

// ── Question bank ──────────────────────────────────────────────
function getQuestionsForSkill(string $skill): array {
    $skill = strtolower(trim($skill));

    // Generic fallback bank per topic keyword
    $banks = [

        // ── Python ────────────────────────────────────────────
        'python' => [
            ['question'=>'What is the output of `print(type([]))`?','options'=>['<class \'list\'>','<class \'array\'>','list','Array'],'correct_index'=>0],
            ['question'=>'Which keyword is used to define a function in Python?','options'=>['function','def','fun','define'],'correct_index'=>1],
            ['question'=>'What does `len("hello")` return?','options'=>['4','5','6','None'],'correct_index'=>1],
            ['question'=>'How do you create a virtual environment in Python?','options'=>['pip venv','python -m venv','virtualenv create','env new'],'correct_index'=>1],
            ['question'=>'What is a list comprehension in Python?','options'=>['A way to compress files','A concise way to create lists','A loop macro','A built-in sort'],'correct_index'=>1],
            ['question'=>'What does the `*args` syntax do in Python?','options'=>['Multiplies arguments','Passes a variable number of positional arguments','Passes keyword arguments','Unpacks a dictionary'],'correct_index'=>1],
            ['question'=>'What is a Python decorator?','options'=>['A design pattern','A function that wraps another function','A class method','A lambda expression'],'correct_index'=>1],
            ['question'=>'What module do you use for regular expressions in Python?','options'=>['regex','re','regexp','pattern'],'correct_index'=>1],
            ['question'=>'What is the GIL in Python?','options'=>['Global Import Library','Global Interpreter Lock','General Iteration Loop','Grand Index List'],'correct_index'=>1],
            ['question'=>'What does `dict.get(key, default)` return if key is missing?','options'=>['None','KeyError','default','False'],'correct_index'=>2],
            ['question'=>'How is multiple inheritance declared in Python?','options'=>['class A : B, C','class A extends B, C','class A(B, C)','class A inherits B, C'],'correct_index'=>2],
            ['question'=>'Which data structure is mutable in Python?','options'=>['tuple','frozenset','string','list'],'correct_index'=>3],
            ['question'=>'What is the output of `bool("")`?','options'=>['True','None','Error','False'],'correct_index'=>3],
        ],

        // ── JavaScript ────────────────────────────────────────
        'javascript' => [
            ['question'=>'Which operator performs strict equality in JS?','options'=>['==','=','!=','==='],'correct_index'=>3],
            ['question'=>'What does `typeof null` return?','options'=>['null','undefined','boolean','object'],'correct_index'=>3],
            ['question'=>'What is a closure in JavaScript?','options'=>['A terminated function','A function with access to its outer scope','An arrow function','A promise'],'correct_index'=>1],
            ['question'=>'Which method converts JSON string to object?','options'=>['JSON.parse()','JSON.stringify()','JSON.convert()','JSON.toObject()'],'correct_index'=>0],
            ['question'=>'What is the event loop in JavaScript?','options'=>['A for loop variant','Mechanism to handle async operations','A DOM event','A timer'],'correct_index'=>1],
            ['question'=>'What does `Array.prototype.map()` return?','options'=>['void','The original array','A new array','A boolean'],'correct_index'=>2],
            ['question'=>'How do you declare a constant in JS?','options'=>['var','let','const','fixed'],'correct_index'=>2],
            ['question'=>'What is a Promise?','options'=>['A guarantee of success','An async operation placeholder','A callback function','A class method'],'correct_index'=>1],
            ['question'=>'What does `===` check vs `==`?','options'=>['Both same','Type + value vs value only','Value vs type','Nothing'],'correct_index'=>1],
            ['question'=>'What is the DOM?','options'=>['Document Object Model','Data Object Model','Dynamic Object Method','None'],'correct_index'=>0],
        ],

        // ── Figma / Design ────────────────────────────────────
        'figma' => [
            ['question'=>'What is Auto Layout in Figma?','options'=>['A plugin','A constraint system for resizable components','A color palette tool','A grid system'],'correct_index'=>1],
            ['question'=>'What are Figma components used for?','options'=>['Colour themes','Reusable UI elements','Exporting assets','None of the above'],'correct_index'=>1],
            ['question'=>'What is a Figma variant?','options'=>['A bug fix','Different states of a component','A font setting','A gradient option'],'correct_index'=>1],
            ['question'=>'How do you create a prototype in Figma?','options'=>['Plugins tab','Prototype tab with interactions','Export tab','None'],'correct_index'=>1],
            ['question'=>'What is the purpose of Figma frames?','options'=>['Add effects','Containers for UI screens','Group layers','None'],'correct_index'=>1],
            ['question'=>'What is the difference between Group and Frame in Figma?','options'=>['No difference','Frames clip content; groups don\'t','Groups clip content; frames don\'t','Both clip'],'correct_index'=>1],
            ['question'=>'What file format can Figma export to?','options'=>['MP4 only','PNG, SVG, PDF, JPG','HTML only','PSD only'],'correct_index'=>1],
            ['question'=>'What is Figma\'s Design Tokens concept?','options'=>['A plugin','Reusable design variables (colors, spacing)','A feature for developers','An AI tool'],'correct_index'=>1],
            ['question'=>'What does "Detach from Component" do in Figma?','options'=>['Deletes the element','Breaks the link to the master component','Groups all layers','None'],'correct_index'=>1],
            ['question'=>'What is Figma\'s primary use case?','options'=>['Video editing','Collaborative UI/UX design','3D modeling','Photo editing'],'correct_index'=>1],
        ],

        // ── Spanish ───────────────────────────────────────────
        'spanish' => [
            ['question'=>'What does "Hablar" mean?','options'=>['To eat','To run','To speak','To think'],'correct_index'=>2],
            ['question'=>'What is "Buenos días"?','options'=>['Good night','Good afternoon','Goodbye','Good morning'],'correct_index'=>3],
            ['question'=>'What does "Gracias" mean?','options'=>['Please','Sorry','Hello','Thank you'],'correct_index'=>3],
            ['question'=>'How do you say "Where is the bathroom?" in Spanish?','options'=>['¿Dónde está el baño?','¿Qué hora es?','¿Cómo estás?','¿Cuánto cuesta?'],'correct_index'=>0],
            ['question'=>'What is the Spanish word for "Water"?','options'=>['Fuego','Aire','Tierra','Agua'],'correct_index'=>3],
            ['question'=>'Which verb means "to be" (permanent state)?','options'=>['Estar','Tener','Ser','Hacer'],'correct_index'=>2],
            ['question'=>'How do you say "I love you" in Spanish?','options'=>['Te quiero','Me gusta','Te odio','Lo siento'],'correct_index'=>0],
            ['question'=>'What is "rojo" in English?','options'=>['Blue','Green','Yellow','Red'],'correct_index'=>3],
            ['question'=>'What is the plural of "el libro"?','options'=>['los libros','las libras','el libros','los libroes'],'correct_index'=>0],
            ['question'=>'Translate: "Yo como arroz"','options'=>['I drink water','I eat rice','I like music','I run fast'],'correct_index'=>1],
        ],

        // ── Guitar / Music ────────────────────────────────────
        'guitar' => [
            ['question'=>'How many strings does a standard guitar have?','options'=>['4','5','6','7'],'correct_index'=>2],
            ['question'=>'What is a capo?','options'=>['A guitar brand','A device to clamp all strings at a fret','A type of pick','A chord'],'correct_index'=>1],
            ['question'=>'Which chord uses the notes E-G#-B?','options'=>['A major','E major','C major','D major'],'correct_index'=>1],
            ['question'=>'What is tablature (tab)?','options'=>['Sheet music notation','Simplified guitar notation showing frets','A type of guitar','A scale'],'correct_index'=>1],
            ['question'=>'What does BPM stand for?','options'=>['Bass Per Minute','Beats Per Measure','Beats Per Minute','Bass Playing Method'],'correct_index'=>2],
            ['question'=>'What is the thinnest string on a standard guitar called?','options'=>['E6','A string','E1 (high E)','B string'],'correct_index'=>2],
            ['question'=>'What is a power chord?','options'=>['A chord with 5 strings','A 2–3 note chord using root and fifth','A barre chord','A jazz chord'],'correct_index'=>1],
            ['question'=>'What does fingerpicking mean?','options'=>['Plucking strings with a pick','Using fingers instead of a pick','A strumming pattern','A guitar technique using nails'],'correct_index'=>1],
            ['question'=>'What is alternate picking?','options'=>['Using two picks','Alternating down and up strokes','Skipping strings','Hybrid picking'],'correct_index'=>1],
            ['question'=>'In standard tuning, what are the 6 guitar strings from low to high?','options'=>['E A D G B E','A E D G B E','E B G D A E','E A D G E B'],'correct_index'=>0],
        ],

        // ── Machine Learning ──────────────────────────────────
        'machine learning' => [
            ['question'=>'What is overfitting in ML?','options'=>['Model too simple','Model performs well on training but poorly on test data','Model with low bias','Model uses too few features'],'correct_index'=>1],
            ['question'=>'What is the purpose of a train/test split?','options'=>['Speed up training','Prevent data leakage and evaluate generalisation','Reduce dataset size','Balance classes'],'correct_index'=>1],
            ['question'=>'Which algorithm is used for classification AND regression?','options'=>['K-Means','PCA','Decision Tree','Apriori'],'correct_index'=>2],
            ['question'=>'What does "gradient descent" minimise?','options'=>['Data points','The loss function','The number of features','The learning rate'],'correct_index'=>1],
            ['question'=>'What is a confusion matrix?','options'=>['A data preprocessing step','A table showing prediction vs actual class','A type of neural network','A clustering result'],'correct_index'=>1],
            ['question'=>'What is a hyperparameter?','options'=>['A parameter learned during training','A parameter set before training','An output prediction','A hidden layer node'],'correct_index'=>1],
            ['question'=>'What is regularisation used for?','options'=>['Speed up training','Prevent underfitting','Reduce overfitting','Increase model complexity'],'correct_index'=>2],
            ['question'=>'What is the activation function in neural networks?','options'=>['A loss function','A function that introduces non-linearity','A normalisation step','A weight initialiser'],'correct_index'=>1],
            ['question'=>'What does CNN stand for?','options'=>['Convolutional Neural Network','Clustered Node Network','Computed Number Net','None of the above'],'correct_index'=>0],
            ['question'=>'What is K-Means used for?','options'=>['Classification','Regression','Clustering','Dimensionality reduction'],'correct_index'=>2],
        ],

        // ── SEO / Marketing ───────────────────────────────────
        'seo' => [
            ['question'=>'What does SEO stand for?','options'=>['Search Engine Operation','Search Engine Optimisation','Site Engine Output','Structured Element Order'],'correct_index'=>1],
            ['question'=>'What is a backlink?','options'=>['A broken link','A link from another site to yours','An internal link','A redirect'],'correct_index'=>1],
            ['question'=>'What is a meta description?','options'=>['The page title','A brief page summary shown in search results','A header tag','A sitemap entry'],'correct_index'=>1],
            ['question'=>'What does CTR stand for?','options'=>['Click-Through Rate','Content Transfer Rate','Core Tag Ratio','None'],'correct_index'=>0],
            ['question'=>'Which Google tool tracks website traffic?','options'=>['Google Ads','Google Analytics','Google Search Console','Both B and C'],'correct_index'=>3],
            ['question'=>'What is a canonical tag?','options'=>['A duplicate page','A tag telling search engines the preferred URL','A 301 redirect','A meta keyword'],'correct_index'=>1],
            ['question'=>'What is keyword stuffing?','options'=>['Finding good keywords','Overusing keywords to manipulate rankings','Organising keywords','None'],'correct_index'=>1],
            ['question'=>'What is the purpose of a sitemap?','options'=>['Design layout','Help search engines discover all pages','Store passwords','Track visitors'],'correct_index'=>1],
            ['question'=>'What is page speed important for in SEO?','options'=>['It\'s not important','Google uses it as a ranking factor','Only for mobile','Only for images'],'correct_index'=>1],
            ['question'=>'What is "organic traffic"?','options'=>['Paid clicks','Social media traffic','Visitors from search results (non-paid)','Email campaigns'],'correct_index'=>2],
        ],

        // ── Photography ───────────────────────────────────────
        'photography' => [
            ['question'=>'What does ISO control?','options'=>['Shutter speed','Aperture','Camera sensor sensitivity','Focus'],'correct_index'=>2],
            ['question'=>'What is aperture measured in?','options'=>['Seconds','ISO values','f-stops','Megapixels'],'correct_index'=>2],
            ['question'=>'What is the "golden hour" in photography?','options'=>['Mid-day sunlight','The hour after sunrise or before sunset','Night photography','Studio lighting'],'correct_index'=>1],
            ['question'=>'What is depth of field?','options'=>['The distance from camera to subject','The range of sharp focus in an image','The camera sensor size','The focal length'],'correct_index'=>1],
            ['question'=>'What does a faster shutter speed do?','options'=>['Blurs motion','Freezes motion','Darkens the image only','Reduces noise'],'correct_index'=>1],
            ['question'=>'What is the rule of thirds?','options'=>['Use 3 light sources','Divide frame into 9 parts and place subject on intersections','Take 3 shots always','Use 3 colours'],'correct_index'=>1],
            ['question'=>'What is RAW format?','options'=>['Compressed image file','Unprocessed image with full sensor data','Low quality JPEG','Black and white format'],'correct_index'=>1],
            ['question'=>'What is bokeh?','options'=>['A camera brand','Blur in background/foreground of a photo','A lens type','A filter effect'],'correct_index'=>1],
            ['question'=>'What does a wide-angle lens do?','options'=>['Zooms in','Captures a broader field of view','Reduces depth of field','Increases ISO'],'correct_index'=>1],
            ['question'=>'What is exposure compensation?','options'=>['A camera brand setting','Adjusting brightness above or below metered exposure','A focus mode','A noise reduction feature'],'correct_index'=>1],
        ],
    ];

    // Match skill to bank key by checking if any key is a substring of the skill
    foreach ($banks as $key => $questions) {
        if (str_contains($skill, $key) || str_contains($key, $skill)) {
            return $questions;
        }
    }

    // Generic fallback for unlisted skills
    return generateGenericQuestions($skill);
}

function generateGenericQuestions(string $skill): array {
    $titleSkill = ucfirst($skill);
    return [
        ['question'=>"Which of the following is a core principle of {$titleSkill}?",'options'=>['Memorisation only','Practice and application','Reading theory only','Avoiding feedback'],'correct_index'=>1],
        ['question'=>"What is the best way to improve at {$titleSkill}?",'options'=>['Passive reading','Consistent deliberate practice','Watching videos only','Copying others'],'correct_index'=>1],
        ['question'=>"Why is receiving feedback important when learning {$titleSkill}?",'options'=>["It isn't",'It helps identify and fix mistakes faster','It slows progress','It only helps beginners'],'correct_index'=>1],
        ['question'=>"How does teaching {$titleSkill} to others help you?",'options'=>['It wastes time','It reinforces your own understanding','It only helps others','No benefit'],'correct_index'=>1],
        ['question'=>"What role does motivation play in learning {$titleSkill}?",'options'=>['None','It sustains effort through challenges','Only at the start','Only for beginners'],'correct_index'=>1],
        ['question'=>"Which mindset is most effective for mastering {$titleSkill}?",'options'=>['Fixed mindset','Comfort-zone mindset','Growth mindset','Passive mindset'],'correct_index'=>2],
        ['question'=>"What is spaced repetition useful for in {$titleSkill}?",'options'=>['Skipping practice','Long-term retention of concepts','Quick cramming','Nothing'],'correct_index'=>1],
        ['question'=>"How important is setting goals when learning {$titleSkill}?",'options'=>['Not important','Very — goals focus effort and measure progress','Only for professionals','Only for beginners'],'correct_index'=>1],
        ['question'=>"What is a good way to track progress in {$titleSkill}?",'options'=>['Guessing','Journaling, projects or tests','Ignoring results','Asking nobody'],'correct_index'=>1],
        ['question'=>"What separates beginners from experts in {$titleSkill}?",'options'=>['Natural talent only','Accumulated deliberate practice','Reading more books','Luck'],'correct_index'=>1],
    ];
}
