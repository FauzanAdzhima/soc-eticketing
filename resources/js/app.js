import './bootstrap';
import Chart from 'chart.js/auto';
import './ticket-report-editor';

window.Chart = Chart;

document.addEventListener('livewire:navigated', () => {
    try {
        const stored = localStorage.getItem('theme');
        const prefersDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches;
        const useDark = stored === 'dark' || ((stored === null || stored === 'system') && prefersDark);
        document.documentElement.classList.toggle('dark', !!useDark);
    } catch (e) {}
});

document.addEventListener('livewire:init', () => {
    Livewire.hook('request', ({ fail }) => {
        fail(({ status }) => {
            if ([0, 401, 404, 419, 500].includes(status)) {
                window.location.reload();
            }
        });
    });
});
