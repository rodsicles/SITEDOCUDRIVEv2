/**
 * Chart.js Lazy Loader
 * Only loads Chart.js when needed on the page
 */

import { Chart, registerables } from 'chart.js';

// Register all Chart.js components
Chart.register(...registerables);

// Make Chart available globally
window.Chart = Chart;

// Export for module usage
export default Chart;
