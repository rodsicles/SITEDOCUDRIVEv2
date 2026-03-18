/**
 * FullCalendar Lazy Loader
 * Only loads FullCalendar when needed on the page
 */

import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';

// Make Calendar available globally
window.Calendar = Calendar;
window.dayGridPlugin = dayGridPlugin;
window.interactionPlugin = interactionPlugin;

// Export for module usage
export { Calendar, dayGridPlugin, interactionPlugin };
