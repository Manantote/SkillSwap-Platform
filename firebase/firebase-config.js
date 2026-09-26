/**
 * Skill Swap — Firebase Configuration
 *
 * HOW TO SET UP:
 * 1. Go to https://console.firebase.google.com/
 * 2. Create a project (or use an existing one)
 * 3. Go to Project Settings → General → Your Apps → Add Web App
 * 4. Copy the firebaseConfig object and paste it below, replacing the placeholder values.
 * 5. In Firebase Console → Authentication → Sign-in method → Enable "Email/Password"
 */

// ── Replace ALL placeholder values with your real Firebase project config ──
const firebaseConfig = {
  apiKey: "AIzaSyDYWrmFjQ3_IqJ0VMA2_tS5YMGtnkthkSY",
  authDomain: "skillswap-f4cf0.firebaseapp.com",
  projectId: "skillswap-f4cf0",
  storageBucket: "skillswap-f4cf0.firebasestorage.app",
  messagingSenderId: "1047344915734",
  appId: "1:1047344915734:web:bfc1dd95a7d6fe52059c37",
  measurementId: "G-S1NCDN4YWD"
};

// ── Initialise Firebase (using Firebase Compat SDK loaded via CDN) ──────────
// This file is always loaded AFTER the Firebase CDN scripts in each HTML page.
if (!firebase.apps.length) {
  firebase.initializeApp(firebaseConfig);
}

const auth = firebase.auth();

// ── Persistence: keep the user logged in across page refreshes ──────────────
auth.setPersistence(firebase.auth.Auth.Persistence.LOCAL);

// ── Export for use in other scripts ─────────────────────────────────────────
// (accessible globally — no modules needed in this plain HTML setup)
window.ssAuth = auth;

// ── Auth state observer ──────────────────────────────────────────────────────
// Sets window.currentFirebaseUser whenever auth state changes.
auth.onAuthStateChanged(user => {
  window.currentFirebaseUser = user || null;
});
