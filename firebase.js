import { initializeApp } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-app.js";
import { getFirestore } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-firestore.js";

const firebaseConfig = {
  apiKey: "AIzaSyDRW7iu0TIRDyQYyupCYs0DNQEG5IWY5_0",
  authDomain: "library-56d70.firebaseapp.com",
  projectId: "library-56d70",
  storageBucket: "library-56d70.firebasestorage.app",
  messagingSenderId: "618124178466",
  appId: "1:618124178466:web:b4824136c1b2b5978fc67b",
  measurementId: "G-BJ48LSDHPC"
};

const app = initializeApp(firebaseConfig);
export const db = getFirestore(app);
