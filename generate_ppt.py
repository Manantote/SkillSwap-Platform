#!/usr/bin/env python3
"""
SkillSwap – Final Year Project Presentation Generator
Light, modern, startup-style theme with purple/blue/white palette.
"""

from pptx import Presentation
from pptx.util import Inches, Pt, Emu
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN, MSO_ANCHOR
from pptx.enum.shapes import MSO_SHAPE

# ─── Dimensions ─────────────────────────────────────────────────────────────────
SW = Inches(13.333)
SH = Inches(7.5)

# ─── Light Color Palette ────────────────────────────────────────────────────────
WHITE         = RGBColor(0xFF, 0xFF, 0xFF)
BG_LIGHT      = RGBColor(0xF8, 0xF9, 0xFC)   # Soft off-white page background
BG_CARD       = RGBColor(0xFF, 0xFF, 0xFF)     # Pure white cards
PURPLE        = RGBColor(0x6C, 0x63, 0xFF)     # Primary brand purple
PURPLE_DARK   = RGBColor(0x4B, 0x44, 0xCE)     # Darker purple for emphasis
PURPLE_LIGHT  = RGBColor(0xED, 0xEB, 0xFF)     # Very light purple tint
BLUE          = RGBColor(0x35, 0x7A, 0xBD)     # Deep blue accent
BLUE_LIGHT    = RGBColor(0xE8, 0xF1, 0xFD)     # Light blue tint
TEAL          = RGBColor(0x10, 0xB9, 0x81)     # Green-teal for success
TEAL_LIGHT    = RGBColor(0xE6, 0xFA, 0xF2)
PINK          = RGBColor(0xEF, 0x44, 0x6D)     # Accent pink/red
PINK_LIGHT    = RGBColor(0xFD, 0xEC, 0xF0)
ORANGE        = RGBColor(0xF5, 0x9E, 0x0B)
ORANGE_LIGHT  = RGBColor(0xFE, 0xF3, 0xC7)
TEXT_DARK     = RGBColor(0x1E, 0x1E, 0x2E)     # Near-black for headings
TEXT_BODY     = RGBColor(0x4A, 0x4A, 0x68)     # Dark gray for body
TEXT_MUTED    = RGBColor(0x9C, 0x9C, 0xB3)     # Light muted gray
BORDER        = RGBColor(0xE2, 0xE4, 0xED)     # Subtle border/line colour
GRADIENT_A    = RGBColor(0x6C, 0x63, 0xFF)
GRADIENT_B    = RGBColor(0xA7, 0x8B, 0xFA)


# ═════════════════════════════════════════════════════════════════════════════════
# SHAPE HELPERS
# ═════════════════════════════════════════════════════════════════════════════════

def set_bg(slide, color=BG_LIGHT):
    fill = slide.background.fill
    fill.solid()
    fill.fore_color.rgb = color

def rect(slide, l, t, w, h, color=BG_CARD, shadow=False):
    s = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, l, t, w, h)
    s.line.fill.background()
    s.fill.solid()
    s.fill.fore_color.rgb = color
    if shadow:
        s.shadow.inherit = False
    return s

def rrect(slide, l, t, w, h, color=BG_CARD):
    s = slide.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, l, t, w, h)
    s.line.fill.background()
    s.fill.solid()
    s.fill.fore_color.rgb = color
    return s

def oval(slide, l, t, sz, color):
    s = slide.shapes.add_shape(MSO_SHAPE.OVAL, l, t, sz, sz)
    s.line.fill.background()
    s.fill.solid()
    s.fill.fore_color.rgb = color
    return s

def grad(slide, l, t, w, h, c1, c2):
    s = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, l, t, w, h)
    s.line.fill.background()
    f = s.fill
    f.gradient()
    f.gradient_stops[0].color.rgb = c1
    f.gradient_stops[0].position = 0.0
    f.gradient_stops[1].color.rgb = c2
    f.gradient_stops[1].position = 1.0
    return s

def grad_rrect(slide, l, t, w, h, c1, c2):
    s = slide.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, l, t, w, h)
    s.line.fill.background()
    f = s.fill
    f.gradient()
    f.gradient_stops[0].color.rgb = c1
    f.gradient_stops[0].position = 0.0
    f.gradient_stops[1].color.rgb = c2
    f.gradient_stops[1].position = 1.0
    return s

def line(slide, l, t, w, color=PURPLE, thick=Pt(3)):
    s = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, l, t, w, thick)
    s.line.fill.background()
    s.fill.solid()
    s.fill.fore_color.rgb = color
    return s

def tb(slide, l, t, w, h, text, sz=18, color=TEXT_DARK, bold=False,
       align=PP_ALIGN.LEFT, font='Calibri', spacing=None):
    """Add a textbox with a single paragraph."""
    box = slide.shapes.add_textbox(l, t, w, h)
    tf = box.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = text
    p.font.size = Pt(sz)
    p.font.color.rgb = color
    p.font.bold = bold
    p.font.name = font
    p.alignment = align
    if spacing is not None:
        p.space_after = Pt(spacing)
    return box

def bullets(slide, l, t, w, h, items, sz=15, color=TEXT_BODY,
            icon="▹", ic=PURPLE):
    box = slide.shapes.add_textbox(l, t, w, h)
    tf = box.text_frame
    tf.word_wrap = True
    for i, item in enumerate(items):
        p = tf.paragraphs[0] if i == 0 else tf.add_paragraph()
        ri = p.add_run()
        ri.text = f"{icon}  "
        ri.font.size = Pt(sz)
        ri.font.color.rgb = ic
        ri.font.name = 'Calibri'
        rt = p.add_run()
        rt.text = item
        rt.font.size = Pt(sz)
        rt.font.color.rgb = color
        rt.font.name = 'Calibri'
        p.space_after = Pt(8)
    return box

def section_header(slide, num, title, subtitle=None):
    """Render the top-of-slide section number, title, and underline."""
    # Number pill
    pill = rrect(slide, Inches(0.8), Inches(0.55), Inches(0.6), Inches(0.35), PURPLE)
    tb(slide, Inches(0.8), Inches(0.56), Inches(0.6), Inches(0.35),
       num, sz=13, color=WHITE, bold=True, align=PP_ALIGN.CENTER)
    # Title
    tb(slide, Inches(1.55), Inches(0.5), Inches(6), Inches(0.55),
       title, sz=32, color=TEXT_DARK, bold=True)
    # Underline
    line(slide, Inches(1.55), Inches(1.15), Inches(1.8), PURPLE)
    if subtitle:
        tb(slide, Inches(1.55), Inches(1.3), Inches(8), Inches(0.4),
           subtitle, sz=14, color=TEXT_MUTED)

def top_bar(slide):
    """Thin gradient bar across the very top."""
    grad(slide, Inches(0), Inches(0), SW, Pt(5), GRADIENT_A, GRADIENT_B)


# ═════════════════════════════════════════════════════════════════════════════════
# DECORATIVE HELPERS
# ═════════════════════════════════════════════════════════════════════════════════

def corner_decor(slide):
    """Soft decorative circles in corners."""
    oval(slide, SW - Inches(2), Inches(-0.7), Inches(2.8), PURPLE_LIGHT)
    oval(slide, Inches(-0.9), SH - Inches(1.8), Inches(2.4), BLUE_LIGHT)

def card_with_icon(slide, x, y, w, h, emoji, title, desc, accent=PURPLE, accent_bg=PURPLE_LIGHT):
    """A white card with colored icon circle, title, and description."""
    c = rrect(slide, x, y, w, h, BG_CARD)
    # border effect
    b = rrect(slide, x, y, w, h, BG_CARD)
    b.line.color.rgb = BORDER
    b.line.width = Pt(1)
    b.fill.background()
    # icon circle
    cs = Inches(0.55)
    oval(slide, x + Inches(0.22), y + Inches(0.22), cs, accent_bg)
    tb(slide, x + Inches(0.22), y + Inches(0.24), cs, cs,
       emoji, sz=18, align=PP_ALIGN.CENTER)
    # title
    tb(slide, x + Inches(0.18), y + Inches(0.9), w - Inches(0.36), Inches(0.35),
       title, sz=14, color=TEXT_DARK, bold=True, align=PP_ALIGN.CENTER)
    # desc
    tb(slide, x + Inches(0.18), y + Inches(1.25), w - Inches(0.36), Inches(0.7),
       desc, sz=10, color=TEXT_MUTED, align=PP_ALIGN.CENTER)
    return c


# ═════════════════════════════════════════════════════════════════════════════════
# SLIDES
# ═════════════════════════════════════════════════════════════════════════════════

def s01_title(prs):
    sl = prs.slides.add_slide(prs.slide_layouts[6])
    set_bg(sl, WHITE)

    # Large gradient hero area (top 65%)
    grad(sl, Inches(0), Inches(0), SW, Inches(5.2), GRADIENT_A, GRADIENT_B)

    # Decorative circles
    oval(sl, SW - Inches(3.5), Inches(-1.2), Inches(5), RGBColor(0x7B, 0x73, 0xFF))
    oval(sl, Inches(-1.5), Inches(2.5), Inches(3.5), RGBColor(0x5B, 0x53, 0xEE))

    # Swap icon
    tb(sl, Inches(0), Inches(1.0), SW, Inches(0.7),
       "⇄", sz=44, color=WHITE, bold=True, align=PP_ALIGN.CENTER)

    # Title
    tb(sl, Inches(1.5), Inches(1.75), Inches(10.3), Inches(0.9),
       "SkillSwap", sz=56, color=WHITE, bold=True, align=PP_ALIGN.CENTER)

    # Subtitle line
    tb(sl, Inches(1.5), Inches(2.65), Inches(10.3), Inches(0.5),
       "A Skill Exchange Platform", sz=24, color=RGBColor(0xE0, 0xDD, 0xFF),
       align=PP_ALIGN.CENTER)

    # Thin divider
    rect(sl, Inches(5.8), Inches(3.35), Inches(1.7), Pt(2), RGBColor(0xFF, 0xFF, 0xFF))

    # Info line on gradient
    tb(sl, Inches(1.5), Inches(3.65), Inches(10.3), Inches(0.4),
       "Final Year Project  •  Computer Engineering  •  2026",
       sz=13, color=RGBColor(0xD0, 0xCD, 0xFF), align=PP_ALIGN.CENTER)

    # White bottom area
    # Name & details on white
    tb(sl, Inches(0), Inches(5.55), SW, Inches(0.4),
       "Presented by", sz=12, color=TEXT_MUTED, align=PP_ALIGN.CENTER)
    tb(sl, Inches(0), Inches(5.9), SW, Inches(0.5),
       "Manan Tote", sz=28, color=PURPLE, bold=True, align=PP_ALIGN.CENTER)
    tb(sl, Inches(0), Inches(6.45), SW, Inches(0.35),
       "Computer Engineering  •  Final Year Project",
       sz=12, color=TEXT_MUTED, align=PP_ALIGN.CENTER)

    # Bottom accent line
    grad(sl, Inches(0), SH - Pt(4), SW, Pt(4), GRADIENT_A, GRADIENT_B)


def s02_problem(prs):
    sl = prs.slides.add_slide(prs.slide_layouts[6])
    set_bg(sl, BG_LIGHT)
    top_bar(sl)
    corner_decor(sl)
    section_header(sl, "01", "Problem Statement",
                   "Why do we need SkillSwap?")

    problems = [
        ("💸", "Expensive Learning",
         "Quality courses and tutors cost money.\nMany students can't afford paid platforms for every skill they need.",
         PINK, PINK_LIGHT),
        ("🔍", "No Discovery Platform",
         "People have amazing skills but no structured way to find others who want to learn them.",
         BLUE, BLUE_LIGHT),
        ("🤝", "Lack of Peer Collaboration",
         "Students rarely get a platform for mutual teaching. Traditional systems are one-directional.",
         TEAL, TEAL_LIGHT),
    ]

    cw = Inches(3.7)
    ch = Inches(3.5)

    for i, (em, ttl, desc, ac, abg) in enumerate(problems):
        x = Inches(0.7) + i * (cw + Inches(0.3))
        y = Inches(2.0)

        c = rrect(sl, x, y, cw, ch, BG_CARD)
        c.line.color.rgb = BORDER
        c.line.width = Pt(1)
        c.fill.background()
        rrect(sl, x, y, cw, ch, BG_CARD)

        # Accent top stripe
        rect(sl, x, y, cw, Pt(4), ac)

        # Icon
        ic = Inches(0.7)
        oval(sl, x + cw/2 - ic/2, y + Inches(0.35), ic, abg)
        tb(sl, x + cw/2 - ic/2, y + Inches(0.4), ic, ic,
           em, sz=26, align=PP_ALIGN.CENTER)

        # Title
        tb(sl, x + Inches(0.2), y + Inches(1.25), cw - Inches(0.4), Inches(0.35),
           ttl, sz=17, color=TEXT_DARK, bold=True, align=PP_ALIGN.CENTER)

        # Desc
        tb(sl, x + Inches(0.25), y + Inches(1.75), cw - Inches(0.5), Inches(1.4),
           desc, sz=12, color=TEXT_BODY, align=PP_ALIGN.CENTER)

    # Bottom callout
    rrect(sl, Inches(2.5), Inches(5.9), Inches(8.3), Inches(0.55), PURPLE_LIGHT)
    tb(sl, Inches(2.5), Inches(5.95), Inches(8.3), Inches(0.45),
       "⚡  SkillSwap solves these problems with a free, peer-to-peer exchange platform.",
       sz=13, color=PURPLE_DARK, bold=True, align=PP_ALIGN.CENTER)


def s03_introduction(prs):
    sl = prs.slides.add_slide(prs.slide_layouts[6])
    set_bg(sl, BG_LIGHT)
    top_bar(sl)
    corner_decor(sl)
    section_header(sl, "02", "Introduction",
                   "What is SkillSwap and how does it work?")

    # Left content area
    rrect(sl, Inches(0.7), Inches(1.95), Inches(6.8), Inches(3.0), BG_CARD)
    tb(sl, Inches(1.0), Inches(2.15), Inches(6.2), Inches(0.4),
       "What is SkillSwap?", sz=20, color=PURPLE, bold=True)

    tb(sl, Inches(1.0), Inches(2.65), Inches(6.2), Inches(1.0),
       "SkillSwap is a web-based platform that enables users to teach and learn "
       "skills from each other — completely free. Instead of paying for courses, "
       "users exchange their expertise in a collaborative peer-to-peer environment.",
       sz=14, color=TEXT_BODY)

    points = [
        "Register with Firebase Authentication (Email, Google, GitHub)",
        "List skills you can teach and skills you want to learn",
        "Get automatically matched with complementary learners",
        "Chat in real-time and exchange knowledge freely"
    ]
    bullets(sl, Inches(1.0), Inches(3.6), Inches(6.2), Inches(1.8),
            points, sz=13, icon="✓", ic=TEAL)

    # Right — Tech stack cards
    tb(sl, Inches(8.0), Inches(2.0), Inches(4.5), Inches(0.4),
       "Tech Stack", sz=18, color=TEXT_DARK, bold=True, align=PP_ALIGN.CENTER)

    techs = [
        ("🐘", "PHP", "Backend REST API", PURPLE, PURPLE_LIGHT),
        ("🗄️", "MySQL", "Relational Database", BLUE, BLUE_LIGHT),
        ("🔥", "Firebase", "Authentication", ORANGE, ORANGE_LIGHT),
        ("⚡", "JavaScript", "Frontend Logic", TEAL, TEAL_LIGHT),
    ]

    for i, (em, name, desc, ac, abg) in enumerate(techs):
        y = Inches(2.55) + i * Inches(0.95)
        rrect(sl, Inches(8.0), y, Inches(4.5), Inches(0.8), BG_CARD)
        # left accent
        rect(sl, Inches(8.0), y, Pt(4), Inches(0.8), ac)
        # icon
        oval(sl, Inches(8.2), y + Inches(0.1), Inches(0.5), abg)
        tb(sl, Inches(8.2), y + Inches(0.12), Inches(0.5), Inches(0.5),
           em, sz=18, align=PP_ALIGN.CENTER)
        tb(sl, Inches(8.85), y + Inches(0.08), Inches(2), Inches(0.3),
           name, sz=14, color=TEXT_DARK, bold=True)
        tb(sl, Inches(8.85), y + Inches(0.4), Inches(3), Inches(0.3),
           desc, sz=10, color=TEXT_MUTED)

    # How it works
    rrect(sl, Inches(0.7), Inches(5.3), Inches(11.9), Inches(1.5), BG_CARD)
    tb(sl, Inches(1.0), Inches(5.4), Inches(11.3), Inches(0.35),
       "How It Works", sz=16, color=PURPLE, bold=True, align=PP_ALIGN.CENTER)

    steps = [
        ("1️⃣", "Register", "Create account\nvia Firebase"),
        ("2️⃣", "Add Skills", "List teach &\nlearn skills"),
        ("3️⃣", "Get Matched", "Algorithm finds\ncompatible users"),
        ("4️⃣", "Send Request", "Connect with\nyour match"),
        ("5️⃣", "Start Learning", "Chat and\nexchange skills"),
    ]
    sw_step = Inches(2.2)
    for i, (em, name, desc) in enumerate(steps):
        x = Inches(0.85) + i * (sw_step + Inches(0.15))
        y = Inches(5.85)
        tb(sl, x, y, sw_step, Inches(0.35),
           f"{em}  {name}", sz=13, color=TEXT_DARK, bold=True, align=PP_ALIGN.CENTER)
        tb(sl, x, y + Inches(0.35), sw_step, Inches(0.5),
           desc, sz=10, color=TEXT_MUTED, align=PP_ALIGN.CENTER)
        if i < len(steps) - 1:
            tb(sl, x + sw_step + Inches(0.0), y + Inches(0.1), Inches(0.2), Inches(0.3),
               "→", sz=16, color=PURPLE, align=PP_ALIGN.CENTER)


def s04_overview(prs):
    sl = prs.slides.add_slide(prs.slide_layouts[6])
    set_bg(sl, BG_LIGHT)
    top_bar(sl)
    corner_decor(sl)
    section_header(sl, "03", "Website Overview",
                   "Key features of the SkillSwap platform")

    features = [
        ("🔐", "Registration\n& Login", "Secure Firebase Auth\nwith Email, Google\n& GitHub OAuth", PURPLE, PURPLE_LIGHT),
        ("📊", "Interactive\nDashboard", "Profile card, stats,\nactivity feed, and\nquick actions", BLUE, BLUE_LIGHT),
        ("➕", "Add Skills", "Categorize skills as\n'Teach' or 'Learn'\nfor smart matching", TEAL, TEAL_LIGHT),
        ("🎯", "Smart\nMatching", "Algorithm pairs users\nwith complementary\nskills automatically", PINK, PINK_LIGHT),
        ("💬", "Real-Time\nChat", "Instant messaging\nwith swap partners\nand file sharing", ORANGE, ORANGE_LIGHT),
        ("🔔", "Live\nNotifications", "Real-time alerts for\nnew requests,\nmessages & reviews", PURPLE, PURPLE_LIGHT),
    ]

    cols = 3
    cw = Inches(3.7)
    ch = Inches(2.2)
    gx = Inches(0.3)
    gy = Inches(0.3)

    for i, (em, ttl, desc, ac, abg) in enumerate(features):
        col = i % cols
        row = i // cols
        x = Inches(0.65) + col * (cw + gx)
        y = Inches(1.9) + row * (ch + gy)

        rrect(sl, x, y, cw, ch, BG_CARD)
        # top stripe
        rect(sl, x, y, cw, Pt(3), ac)
        # icon
        cs = Inches(0.55)
        oval(sl, x + Inches(0.2), y + Inches(0.25), cs, abg)
        tb(sl, x + Inches(0.2), y + Inches(0.28), cs, cs,
           em, sz=20, align=PP_ALIGN.CENTER)
        # title
        tb(sl, x + Inches(0.9), y + Inches(0.2), cw - Inches(1.1), Inches(0.55),
           ttl, sz=15, color=TEXT_DARK, bold=True)
        # desc
        tb(sl, x + Inches(0.9), y + Inches(0.8), cw - Inches(1.1), Inches(1.2),
           desc, sz=11, color=TEXT_MUTED)


def s05_objectives(prs):
    sl = prs.slides.add_slide(prs.slide_layouts[6])
    set_bg(sl, BG_LIGHT)
    top_bar(sl)
    corner_decor(sl)
    section_header(sl, "04", "Project Objectives",
                   "What we set out to achieve")

    objectives = [
        ("🎯", "Peer-to-Peer Learning",
         "Create a platform where users can exchange skills directly, "
         "eliminating the need for paid courses or intermediaries.",
         PURPLE, PURPLE_LIGHT),
        ("🌐", "Build a Skill Community",
         "Foster a collaborative community of learners and teachers "
         "who help each other grow through knowledge sharing.",
         BLUE, BLUE_LIGHT),
        ("⚡", "Real-Time Communication",
         "Enable instant messaging, live notifications, and real-time "
         "status updates for seamless and responsive interaction.",
         TEAL, TEAL_LIGHT),
        ("✨", "User-Friendly Interface",
         "Design a modern, responsive, and intuitive UI that provides "
         "a premium experience across all devices and screen sizes.",
         ORANGE, ORANGE_LIGHT),
    ]

    cw = Inches(5.85)
    ch = Inches(1.3)

    for i, (em, ttl, desc, ac, abg) in enumerate(objectives):
        col = i % 2
        row = i // 2
        x = Inches(0.7) + col * (cw + Inches(0.3))
        y = Inches(2.0) + row * (ch + Inches(0.25))

        rrect(sl, x, y, cw, ch, BG_CARD)
        rect(sl, x, y, Pt(4), ch, ac)

        # Icon
        cs = Inches(0.6)
        oval(sl, x + Inches(0.2), y + Inches(0.35), cs, abg)
        tb(sl, x + Inches(0.2), y + Inches(0.38), cs, cs,
           em, sz=22, align=PP_ALIGN.CENTER)

        # Title
        tb(sl, x + Inches(1.0), y + Inches(0.12), cw - Inches(1.2), Inches(0.35),
           ttl, sz=16, color=TEXT_DARK, bold=True)
        # Desc
        tb(sl, x + Inches(1.0), y + Inches(0.5), cw - Inches(1.2), Inches(0.7),
           desc, sz=11, color=TEXT_BODY)

    # Summary
    rrect(sl, Inches(0.7), Inches(4.9), Inches(11.9), Inches(2.0), BG_CARD)
    tb(sl, Inches(1.0), Inches(5.05), Inches(11.3), Inches(0.35),
       "Key Metrics Targeted", sz=16, color=PURPLE, bold=True, align=PP_ALIGN.CENTER)

    metrics = [
        ("100%", "Free Platform", "No cost to use"),
        ("< 2 min", "Setup Time", "Quick registration"),
        ("Real-Time", "Chat & Alerts", "Instant messaging"),
        ("Smart", "Matching", "Algorithm-powered"),
    ]
    for i, (val, label, sub) in enumerate(metrics):
        x = Inches(1.3) + i * Inches(2.9)
        tb(sl, x, Inches(5.5), Inches(2.5), Inches(0.45),
           val, sz=28, color=PURPLE, bold=True, align=PP_ALIGN.CENTER)
        tb(sl, x, Inches(5.95), Inches(2.5), Inches(0.3),
           label, sz=13, color=TEXT_DARK, bold=True, align=PP_ALIGN.CENTER)
        tb(sl, x, Inches(6.25), Inches(2.5), Inches(0.25),
           sub, sz=10, color=TEXT_MUTED, align=PP_ALIGN.CENTER)


def s06_screenshots(prs):
    sl = prs.slides.add_slide(prs.slide_layouts[6])
    set_bg(sl, BG_LIGHT)
    top_bar(sl)
    section_header(sl, "05", "Working System",
                   "Screenshots of the live platform (replace placeholders)")

    screens = [
        ("Login Page", "Firebase Auth\nEmail + OAuth", PURPLE),
        ("Dashboard", "Profile & Stats\nActivity Feed", BLUE),
        ("Skill Matching", "Smart Algorithm\nMatching Engine", TEAL),
        ("Chat System", "Real-Time\nMessaging", ORANGE),
        ("Requests", "Accept / Reject\nSwap Requests", PINK),
    ]

    cw = Inches(2.3)
    ch = Inches(4.2)
    sx = Inches(0.45)
    gap = Inches(0.2)

    for i, (name, desc, ac) in enumerate(screens):
        x = sx + i * (cw + gap)
        y = Inches(1.85)

        rrect(sl, x, y, cw, ch, BG_CARD)
        # Color top bar
        rect(sl, x, y, cw, Pt(4), ac)

        # Screenshot placeholder area
        rrect(sl, x + Inches(0.12), y + Inches(0.2), cw - Inches(0.24), Inches(2.5), BG_LIGHT)
        tb(sl, x + Inches(0.12), y + Inches(0.9), cw - Inches(0.24), Inches(0.9),
           "📷\nInsert\nScreenshot", sz=13, color=TEXT_MUTED, align=PP_ALIGN.CENTER)

        # Name
        tb(sl, x + Inches(0.1), y + Inches(2.9), cw - Inches(0.2), Inches(0.35),
           name, sz=14, color=TEXT_DARK, bold=True, align=PP_ALIGN.CENTER)
        # Desc
        tb(sl, x + Inches(0.1), y + Inches(3.3), cw - Inches(0.2), Inches(0.7),
           desc, sz=10, color=TEXT_MUTED, align=PP_ALIGN.CENTER)

    # Footer note
    rrect(sl, Inches(2), Inches(6.35), Inches(9.3), Inches(0.5), PURPLE_LIGHT)
    tb(sl, Inches(2), Inches(6.38), Inches(9.3), Inches(0.45),
       "🌐  Live project hosted on InfinityFree  —  Replace placeholders with actual screenshots",
       sz=12, color=PURPLE_DARK, align=PP_ALIGN.CENTER)


def s07_database(prs):
    sl = prs.slides.add_slide(prs.slide_layouts[6])
    set_bg(sl, BG_LIGHT)
    top_bar(sl)
    corner_decor(sl)
    section_header(sl, "06", "Database Design",
                   "MySQL tables powering SkillSwap")

    tables = [
        ("👤", "users", "id  •  firebase_uid  •  name\nemail  •  is_online  •  last_seen\ncreated_at",
         PURPLE, PURPLE_LIGHT),
        ("🛠️", "skills", "id  •  name\ncreated_at",
         BLUE, BLUE_LIGHT),
        ("🔗", "user_skills", "id  •  user_id  •  skill_id\ntype (teach / learn)",
         TEAL, TEAL_LIGHT),
        ("📩", "skill_requests", "id  •  sender_id  •  receiver_id\nmessage  •  status  •  created_at",
         ORANGE, ORANGE_LIGHT),
        ("💬", "chat", "id  •  sender_id  •  receiver_id\nmessage  •  created_at",
         PINK, PINK_LIGHT),
        ("🔔", "notifications", "id  •  user_id  •  type\nmessage  •  is_read  •  created_at",
         PURPLE, PURPLE_LIGHT),
        ("⭐", "reviews", "id  •  reviewer_id  •  reviewed_id\nrating  •  comment  •  tags",
         BLUE, BLUE_LIGHT),
        ("🚫", "blocked_users\n& reports", "blocker_id  •  blocked_id\nreporter_id  •  reason",
         PINK, PINK_LIGHT),
    ]

    cols = 4
    cw = Inches(2.85)
    ch = Inches(2.1)
    gx = Inches(0.2)
    gy = Inches(0.2)

    for i, (em, name, fields, ac, abg) in enumerate(tables):
        col = i % cols
        row = i // cols
        x = Inches(0.55) + col * (cw + gx)
        y = Inches(1.8) + row * (ch + gy)

        rrect(sl, x, y, cw, ch, BG_CARD)
        rect(sl, x, y, cw, Pt(3), ac)

        # Icon + table name
        cs = Inches(0.4)
        oval(sl, x + Inches(0.15), y + Inches(0.2), cs, abg)
        tb(sl, x + Inches(0.15), y + Inches(0.22), cs, cs,
           em, sz=14, align=PP_ALIGN.CENTER)
        tb(sl, x + Inches(0.65), y + Inches(0.2), cw - Inches(0.8), Inches(0.35),
           name, sz=15, color=TEXT_DARK, bold=True)

        # Fields
        tb(sl, x + Inches(0.15), y + Inches(0.75), cw - Inches(0.3), Inches(1.2),
           fields, sz=10, color=TEXT_MUTED)

    # Relationship note
    rrect(sl, Inches(0.55), Inches(6.3), Inches(12.2), Inches(0.55), PURPLE_LIGHT)
    tb(sl, Inches(0.7), Inches(6.35), Inches(11.9), Inches(0.45),
       "🔗  All tables use foreign keys referencing users.id  •  user_skills links users ↔ skills  •  Normalized 3NF design",
       sz=12, color=PURPLE_DARK, align=PP_ALIGN.CENTER)


def s08_er_diagram(prs):
    sl = prs.slides.add_slide(prs.slide_layouts[6])
    set_bg(sl, BG_LIGHT)
    top_bar(sl)
    section_header(sl, "07", "Entity Relationship Diagram",
                   "Visual representation of database relationships")

    # Central Users entity
    cx, cy = Inches(6.1), Inches(3.6)

    entities = [
        ("Users", cx, cy, PURPLE, Inches(1.3)),
        ("Skills", cx - Inches(3.8), cy - Inches(1.1), TEAL, Inches(1.05)),
        ("Requests", cx + Inches(3.8), cy - Inches(1.1), ORANGE, Inches(1.05)),
        ("Chat", cx + Inches(3.8), cy + Inches(1.4), PINK, Inches(1.05)),
        ("Notifications", cx - Inches(3.8), cy + Inches(1.4), BLUE, Inches(1.05)),
        ("Reviews", cx, cy + Inches(2.8), RGBColor(0xF5, 0x9E, 0x0B), Inches(1.05)),
    ]

    # Draw entities
    for name, ex, ey, color, sz in entities:
        oval(sl, ex - sz/2, ey - sz/2, sz, color)
        tb(sl, ex - sz/2, ey - Pt(8), sz, Inches(0.35),
           name, sz=12, color=WHITE, bold=True, align=PP_ALIGN.CENTER)

    # Relationship labels
    rels = [
        ("has many ↔", cx - Inches(1.9), cy - Inches(0.8)),
        ("sends / receives →", cx + Inches(1.6), cy - Inches(0.8)),
        ("exchanges ↔", cx + Inches(1.9), cy + Inches(1.1)),
        ("receives ←", cx - Inches(1.9), cy + Inches(1.1)),
        ("gets ↕", cx + Inches(0.2), cy + Inches(1.5)),
    ]
    for label, lx, ly in rels:
        rrect(sl, lx - Inches(0.5), ly - Inches(0.12), Inches(1.5), Inches(0.28), BG_CARD)
        tb(sl, lx - Inches(0.5), ly - Inches(0.1), Inches(1.5), Inches(0.25),
           label, sz=8, color=TEXT_MUTED, align=PP_ALIGN.CENTER)

    # Legend card
    rrect(sl, Inches(0.5), Inches(6.2), Inches(12.3), Inches(0.7), BG_CARD)
    tb(sl, Inches(0.7), Inches(6.28), Inches(11.9), Inches(0.55),
       "Users ↔ Skills (via user_skills M:N)   •   Users → Requests → Users (sender/receiver)   •   "
       "Users ↔ Chat (bidirectional)   •   Users → Reviews → Users   •   Users ← Notifications",
       sz=11, color=TEXT_BODY, align=PP_ALIGN.CENTER)


def s09_hosting(prs):
    sl = prs.slides.add_slide(prs.slide_layouts[6])
    set_bg(sl, BG_LIGHT)
    top_bar(sl)
    corner_decor(sl)
    section_header(sl, "08", "Hosting & Deployment",
                   "How SkillSwap is deployed for live access")

    # Left panel
    rrect(sl, Inches(0.7), Inches(1.9), Inches(5.8), Inches(4.7), BG_CARD)
    tb(sl, Inches(1.0), Inches(2.1), Inches(5.2), Inches(0.4),
       "🌐  InfinityFree Hosting", sz=20, color=PURPLE, bold=True)
    line(sl, Inches(1.0), Inches(2.6), Inches(2), PURPLE)

    pts = [
        "Free web hosting with PHP 8.x + MySQL support",
        "phpMyAdmin for database management",
        "Custom subdomain automatically provided",
        "5 GB storage with unlimited bandwidth",
        "SSL certificate for secure HTTPS connections",
        "cPanel file manager for easy uploads"
    ]
    bullets(sl, Inches(1.0), Inches(2.8), Inches(5.2), Inches(3.5),
            pts, sz=14, icon="✓", ic=TEAL)

    # Right panel — Architecture
    rrect(sl, Inches(6.85), Inches(1.9), Inches(5.8), Inches(4.7), BG_CARD)
    tb(sl, Inches(7.1), Inches(2.1), Inches(5.3), Inches(0.4),
       "🏗️  Architecture Overview", sz=18, color=TEXT_DARK, bold=True)
    line(sl, Inches(7.1), Inches(2.6), Inches(2), BLUE)

    layers = [
        ("Frontend", "HTML • CSS • JavaScript", "Served by InfinityFree", PURPLE, PURPLE_LIGHT),
        ("Backend API", "PHP REST Endpoints", "28 API files on InfinityFree", BLUE, BLUE_LIGHT),
        ("Database", "MySQL (skillswap_new)", "Managed via phpMyAdmin", TEAL, TEAL_LIGHT),
        ("Authentication", "Firebase Auth", "Google Cloud infrastructure", ORANGE, ORANGE_LIGHT),
    ]

    for i, (title, tech, host, ac, abg) in enumerate(layers):
        y = Inches(2.85) + i * Inches(0.85)
        rrect(sl, Inches(7.1), y, Inches(5.3), Inches(0.7), abg)
        rect(sl, Inches(7.1), y, Pt(4), Inches(0.7), ac)
        tb(sl, Inches(7.3), y + Inches(0.05), Inches(2), Inches(0.3),
           title, sz=13, color=TEXT_DARK, bold=True)
        tb(sl, Inches(9.3), y + Inches(0.05), Inches(1.5), Inches(0.3),
           tech, sz=10, color=TEXT_BODY)
        tb(sl, Inches(10.8), y + Inches(0.05), Inches(1.5), Inches(0.3),
           host, sz=9, color=TEXT_MUTED)
        if i < len(layers) - 1:
            tb(sl, Inches(9.5), y + Inches(0.6), Inches(0.5), Inches(0.25),
               "↓", sz=14, color=TEXT_MUTED, align=PP_ALIGN.CENTER)


def s10_challenges(prs):
    sl = prs.slides.add_slide(prs.slide_layouts[6])
    set_bg(sl, BG_LIGHT)
    top_bar(sl)
    corner_decor(sl)
    section_header(sl, "09", "Challenges Faced",
                   "Problems encountered during development and their solutions")

    challenges = [
        ("⚠️", "MySQL Connection Errors",
         "Schema mismatches caused 500 errors across all 28 API endpoints",
         "Audited all PHP files and rebuilt the complete database schema",
         PINK, PINK_LIGHT),
        ("🌐", "InfinityFree Restrictions",
         "Hosting limits on file size, execution time, and MySQL connections",
         "Optimized queries and reduced API response payloads",
         ORANGE, ORANGE_LIGHT),
        ("🔒", "Firebase Domain Auth",
         "Firebase blocked requests from the InfinityFree hosting domain",
         "Added hosting domain to Firebase authorized origins list",
         PURPLE, PURPLE_LIGHT),
        ("🐛", "API Errors (500, 404)",
         "Missing tables and broken foreign keys crashed backend responses",
         "Implemented global JSON error handler with set_exception_handler",
         BLUE, BLUE_LIGHT),
        ("🔄", "Real-Time Sync Issues",
         "Notification polling and chat updates were inconsistent",
         "Implemented 5-second polling heartbeat with smart caching",
         TEAL, TEAL_LIGHT),
    ]

    cw = Inches(11.9)
    ch = Inches(0.9)

    for i, (em, ttl, prob, sol, ac, abg) in enumerate(challenges):
        y = Inches(2.0) + i * (ch + Inches(0.12))

        rrect(sl, Inches(0.7), y, cw, ch, BG_CARD)
        rect(sl, Inches(0.7), y, Pt(4), ch, ac)

        # Icon
        cs = Inches(0.45)
        oval(sl, Inches(0.9), y + Inches(0.22), cs, abg)
        tb(sl, Inches(0.9), y + Inches(0.24), cs, cs,
           em, sz=16, align=PP_ALIGN.CENTER)

        # Challenge title
        tb(sl, Inches(1.55), y + Inches(0.08), Inches(2.5), Inches(0.3),
           ttl, sz=14, color=TEXT_DARK, bold=True)
        # Problem
        tb(sl, Inches(4.1), y + Inches(0.08), Inches(4), Inches(0.7),
           prob, sz=10, color=TEXT_BODY)
        # Solution
        tb(sl, Inches(8.3), y + Inches(0.08), Inches(4), Inches(0.7),
           f"✅ {sol}", sz=10, color=TEAL)


def s11_future(prs):
    sl = prs.slides.add_slide(prs.slide_layouts[6])
    set_bg(sl, BG_LIGHT)
    top_bar(sl)
    corner_decor(sl)
    section_header(sl, "10", "Future Enhancements",
                   "Planned features and improvements")

    enhancements = [
        ("📹", "Video Calling",
         "Built-in video calls\nfor live skill exchange\nlessons between users",
         PURPLE, PURPLE_LIGHT),
        ("🤖", "AI Matching",
         "Machine learning\nalgorithm to improve\nskill compatibility",
         BLUE, BLUE_LIGHT),
        ("📱", "Mobile App",
         "Native Android &\niOS app using\nReact Native",
         TEAL, TEAL_LIGHT),
        ("✨", "Better UI/UX",
         "Advanced animations,\naccessibility, and\nmulti-language support",
         ORANGE, ORANGE_LIGHT),
        ("💳", "Premium Tier",
         "Optional payment for\npriority matching and\nverified badges",
         PINK, PINK_LIGHT),
    ]

    cw = Inches(2.2)
    ch = Inches(3.3)
    sx = Inches(0.65)
    gap = Inches(0.25)

    for i, (em, name, desc, ac, abg) in enumerate(enhancements):
        x = sx + i * (cw + gap)
        y = Inches(1.9)

        rrect(sl, x, y, cw, ch, BG_CARD)
        rect(sl, x, y, cw, Pt(3), ac)

        cs = Inches(0.7)
        oval(sl, x + cw/2 - cs/2, y + Inches(0.25), cs, abg)
        tb(sl, x + cw/2 - cs/2, y + Inches(0.3), cs, cs,
           em, sz=24, align=PP_ALIGN.CENTER)

        tb(sl, x + Inches(0.1), y + Inches(1.15), cw - Inches(0.2), Inches(0.35),
           name, sz=15, color=TEXT_DARK, bold=True, align=PP_ALIGN.CENTER)
        tb(sl, x + Inches(0.1), y + Inches(1.6), cw - Inches(0.2), Inches(1.2),
           desc, sz=11, color=TEXT_MUTED, align=PP_ALIGN.CENTER)

    # Roadmap
    rrect(sl, Inches(0.65), Inches(5.5), Inches(12), Inches(1.2), BG_CARD)
    tb(sl, Inches(0.9), Inches(5.6), Inches(11.5), Inches(0.35),
       "📅  Development Roadmap", sz=15, color=PURPLE, bold=True, align=PP_ALIGN.CENTER)

    phases = [
        ("Phase 1", "Video Calling\nQ3 2026", PURPLE),
        ("Phase 2", "AI Matching\nQ4 2026", BLUE),
        ("Phase 3", "Mobile App\nQ1 2027", TEAL),
        ("Phase 4", "Premium Tier\nQ2 2027", ORANGE),
    ]
    for i, (phase, desc, ac) in enumerate(phases):
        x = Inches(1.2) + i * Inches(2.9)
        y = Inches(6.0)
        rrect(sl, x, y, Inches(2.4), Inches(0.55), abg)
        oval(sl, x + Inches(0.05), y + Inches(0.08), Inches(0.35), ac)
        tb(sl, x + Inches(0.05), y + Inches(0.1), Inches(0.35), Inches(0.35),
           str(i + 1), sz=11, color=WHITE, bold=True, align=PP_ALIGN.CENTER)
        tb(sl, x + Inches(0.5), y + Inches(0.02), Inches(1.8), Inches(0.5),
           f"{phase}: {desc.split(chr(10))[0]}", sz=10, color=TEXT_DARK, bold=True)


def s12_conclusion(prs):
    sl = prs.slides.add_slide(prs.slide_layouts[6])
    set_bg(sl, BG_LIGHT)
    top_bar(sl)
    corner_decor(sl)
    section_header(sl, "11", "Conclusion",
                   "Summary and key takeaways")

    # Main card
    rrect(sl, Inches(0.7), Inches(1.9), Inches(11.9), Inches(3.2), BG_CARD)

    conclusions = [
        "SkillSwap successfully connects learners and teachers on a single, unified platform",
        "Provides real-time interaction through chat, notifications, and intelligent skill matching",
        "Built with a modern, production-ready tech stack — PHP, MySQL, Firebase, JavaScript",
        "Designed to be useful for students, professionals, and lifelong learners",
        "Fully deployed and tested on InfinityFree hosting with phpMyAdmin database management",
        "Scalable architecture ready for future enhancements including video calling and AI matching"
    ]
    bullets(sl, Inches(1.1), Inches(2.1), Inches(11), Inches(2.8),
            conclusions, sz=15, icon="✦", ic=PURPLE)

    # Learning outcomes
    rrect(sl, Inches(0.7), Inches(5.35), Inches(5.7), Inches(1.55), BG_CARD)
    tb(sl, Inches(1.0), Inches(5.45), Inches(5.0), Inches(0.35),
       "📚  Learning Outcomes", sz=15, color=PURPLE, bold=True)
    outcomes = [
        "Full-stack web development with PHP & MySQL",
        "Firebase Authentication integration",
        "REST API design and implementation",
        "Database schema design and optimization"
    ]
    bullets(sl, Inches(1.0), Inches(5.85), Inches(5.0), Inches(1.0),
            outcomes, sz=11, icon="•", ic=TEAL)

    # Real-world usefulness
    rrect(sl, Inches(6.7), Inches(5.35), Inches(5.9), Inches(1.55), BG_CARD)
    tb(sl, Inches(7.0), Inches(5.45), Inches(5.3), Inches(0.35),
       "🌍  Real-World Impact", sz=15, color=PURPLE, bold=True)
    impacts = [
        "Democratizes skill-sharing for all backgrounds",
        "Reduces dependency on expensive learning platforms",
        "Builds collaborative professional networks",
        "Promotes lifelong learning culture"
    ]
    bullets(sl, Inches(7.0), Inches(5.85), Inches(5.3), Inches(1.0),
            impacts, sz=11, icon="•", ic=BLUE)


def s13_thank_you(prs):
    sl = prs.slides.add_slide(prs.slide_layouts[6])
    set_bg(sl, WHITE)

    # Hero gradient
    grad(sl, Inches(0), Inches(0), SW, Inches(5), GRADIENT_A, GRADIENT_B)

    # Decorative circles
    oval(sl, SW - Inches(3), Inches(-0.8), Inches(4), RGBColor(0x7B, 0x73, 0xFF))
    oval(sl, Inches(-1), Inches(2.2), Inches(3), RGBColor(0x5B, 0x53, 0xEE))

    # Thank You text
    tb(sl, Inches(0), Inches(1.5), SW, Inches(0.9),
       "Thank You!", sz=52, color=WHITE, bold=True, align=PP_ALIGN.CENTER)

    tb(sl, Inches(0), Inches(2.5), SW, Inches(0.5),
       "SkillSwap — A Skill Exchange Platform", sz=20,
       color=RGBColor(0xE0, 0xDD, 0xFF), align=PP_ALIGN.CENTER)

    rect(sl, Inches(5.8), Inches(3.2), Inches(1.7), Pt(2), WHITE)

    tb(sl, Inches(0), Inches(3.5), SW, Inches(0.4),
       "Questions & Discussion", sz=18,
       color=RGBColor(0xD0, 0xCD, 0xFF), align=PP_ALIGN.CENTER)

    # Bottom white area with contact
    tb(sl, Inches(0), Inches(5.4), SW, Inches(0.35),
       "Presented by", sz=12, color=TEXT_MUTED, align=PP_ALIGN.CENTER)
    tb(sl, Inches(0), Inches(5.7), SW, Inches(0.5),
       "Manan Tote", sz=26, color=PURPLE, bold=True, align=PP_ALIGN.CENTER)
    tb(sl, Inches(0), Inches(6.25), SW, Inches(0.35),
       "Computer Engineering  •  Final Year Project  •  2026",
       sz=12, color=TEXT_MUTED, align=PP_ALIGN.CENTER)

    # Bottom accent
    grad(sl, Inches(0), SH - Pt(4), SW, Pt(4), GRADIENT_A, GRADIENT_B)


# ═════════════════════════════════════════════════════════════════════════════════
# MAIN
# ═════════════════════════════════════════════════════════════════════════════════

def main():
    prs = Presentation()
    prs.slide_width = SW
    prs.slide_height = SH

    print("🎨 Generating SkillSwap Presentation (Light Theme)...")

    builders = [
        (s01_title,       "Title"),
        (s02_problem,     "Problem Statement"),
        (s03_introduction,"Introduction"),
        (s04_overview,    "Website Overview"),
        (s05_objectives,  "Project Objectives"),
        (s06_screenshots, "Working System Screenshots"),
        (s07_database,    "Database Design"),
        (s08_er_diagram,  "ER Diagram"),
        (s09_hosting,     "Hosting & Deployment"),
        (s10_challenges,  "Challenges Faced"),
        (s11_future,      "Future Enhancements"),
        (s12_conclusion,  "Conclusion"),
        (s13_thank_you,   "Thank You"),
    ]

    for fn, name in builders:
        fn(prs)
        print(f"  ✅ {name}")

    out = '/Users/mananvivekanandtote/Documents/wd/skillswap/SkillSwap_Presentation.pptx'
    prs.save(out)
    print(f"\n🎉 Presentation saved to:\n   {out}")
    print(f"   Total slides: {len(prs.slides)}")

if __name__ == '__main__':
    main()
