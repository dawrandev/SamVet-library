import ApexCharts from 'apexcharts';

const FONT = 'Outfit Variable, Outfit, sans-serif';

/**
 * Render a donut chart into an element that carries its data via data-* attributes:
 *   data-series  JSON array of numbers
 *   data-labels  JSON array of labels
 *   data-colors  JSON array of hex colors
 *   data-center  center total label (e.g. "Nusxa")
 * Empty data (all zero) renders a neutral grey ring with a "0" total.
 */
const donut = (el) => {
    const series = JSON.parse(el.dataset.series || '[]');
    const labels = JSON.parse(el.dataset.labels || '[]');
    const colors = JSON.parse(el.dataset.colors || '[]');
    const center = el.dataset.center || 'Jami';
    const total = series.reduce((a, b) => a + Number(b), 0);
    const empty = total === 0;

    new ApexCharts(el, {
        chart: { type: 'donut', height: 260, width: '100%', fontFamily: FONT, animations: { enabled: false } },
        series: empty ? [1] : series,
        labels: empty ? ['—'] : labels,
        colors: empty ? ['#e4e7ec'] : colors,
        stroke: { width: 0 },
        dataLabels: { enabled: false },
        legend: {
            position: 'bottom',
            fontSize: '13px',
            fontFamily: FONT,
            labels: { colors: '#667085' },
            markers: { radius: 12 },
            itemMargin: { horizontal: 8, vertical: 2 },
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '68%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: center,
                            fontSize: '13px',
                            color: '#98a2b3',
                            formatter: () => String(total),
                        },
                        value: { fontSize: '26px', fontWeight: 700, color: '#1d2939' },
                    },
                },
            },
        },
        tooltip: { enabled: !empty },
        states: { hover: { filter: { type: empty ? 'none' : 'lighten' } } },
    }).render();
};

/**
 * Initialize every dashboard donut on the page.
 */
export const initDashboardCharts = () => {
    document.querySelectorAll('[data-donut]').forEach(donut);
};
