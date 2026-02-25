import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import App from './App.tsx'

// --- Console Warning Suppressor ---
// Override per silenziare avvisi di depracation upstream di react-globe.gl
const originalWarn = console.warn;
console.warn = (...args) => {
  if (args[0] && typeof args[0] === 'string') {
    if (args[0].includes('THREE.Clock: This module has been deprecated')) return;
    if (args[0].includes('Multiple instances of Three.js')) return;
  }
  originalWarn(...args);
};
// ----------------------------------

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <App />
  </StrictMode>,
)
