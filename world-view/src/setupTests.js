import '@testing-library/jest-dom';
import { vi } from 'vitest';

// Mock per canvas e resize observer (spesso richiesti in mock UI)
global.ResizeObserver = class {
    observe() { }
    unobserve() { }
    disconnect() { }
};
