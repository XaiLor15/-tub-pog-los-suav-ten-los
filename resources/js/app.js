// Core UI (every page)
import './motion';
import './ui';
import './theme';
import './tooltip';
import './sidebar';
import './search';
import './notifications';
import './lightbox';

// Page modules (loaded only where needed)
if (document.querySelector('[data-member-form]')) import('./member-form');
if (document.getElementById('tree-viewport')) import('./tree');
if (document.querySelector('[data-similar-tx],[data-money-preview]')) import('./finance');
if (document.querySelector('[data-member-picker]')) import('./picker');
