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
 * Render a daily-usage line chart from data-* attributes:
 *   data-dates    JSON array of "Y-m-d" x-axis categories
 *   data-series   JSON array of {name, data, color, dashed?} objects
 * All series share one axis (counts), so a single y-scale is correct here —
 * never split these into a dual-axis chart.
 */
const line = (el) => {
    const dates = JSON.parse(el.dataset.dates || '[]');
    const series = JSON.parse(el.dataset.series || '[]');

    new ApexCharts(el, {
        chart: { type: 'line', height: 300, width: '100%', fontFamily: FONT, toolbar: { show: false }, animations: { enabled: false } },
        series: series.map((s) => ({ name: s.name, data: s.data })),
        colors: series.map((s) => s.color),
        stroke: {
            width: series.map((s) => (s.dashed ? 2 : 2.5)),
            curve: 'smooth',
            dashArray: series.map((s) => (s.dashed ? 5 : 0)),
        },
        xaxis: {
            categories: dates,
            labels: { style: { colors: '#98a2b3', fontSize: '11px', fontFamily: FONT } },
            axisBorder: { show: false },
            axisTicks: { show: false },
        },
        yaxis: {
            labels: { style: { colors: '#98a2b3', fontSize: '11px', fontFamily: FONT }, formatter: (v) => Math.round(v) },
            min: 0,
        },
        grid: { borderColor: '#f2f4f7', strokeDashArray: 4 },
        legend: {
            position: 'top',
            horizontalAlign: 'left',
            fontSize: '13px',
            fontFamily: FONT,
            labels: { colors: '#667085' },
            markers: { radius: 12 },
            itemMargin: { horizontal: 10, vertical: 4 },
        },
        markers: { size: 0, hover: { size: 5 } },
        tooltip: { shared: true, intersect: false, x: { format: 'dd.MM.yyyy' } },
        dataLabels: { enabled: false },
    }).render();
};

/**
 * Render a vertical bar chart with a "nusxa / nomi" toggle, from data-* attributes:
 *   data-labels          JSON array of category labels
 *   data-colors           JSON array of hex colors, one per category (fixed order)
 *   data-series-copies    JSON array of counts (nusxa)
 *   data-series-titles    JSON array of counts (nomi)
 * Toggle buttons live in a sibling [data-bar-toggle-group] and carry [data-bar-mode].
 */
const bar = (el) => {
    const labels = JSON.parse(el.dataset.labels || '[]');
    const colors = JSON.parse(el.dataset.colors || '[]');
    const seriesCopies = JSON.parse(el.dataset.seriesCopies || '[]');
    const seriesTitles = JSON.parse(el.dataset.seriesTitles || '[]');
    const nameFor = (mode) => (mode === 'titles' ? el.dataset.labelTitles || 'Nomi' : el.dataset.labelCopies || 'Nusxa');
    const dataFor = (mode) => (mode === 'titles' ? seriesTitles : seriesCopies);

    const chart = new ApexCharts(el, {
        chart: { type: 'bar', height: 300, width: '100%', fontFamily: FONT, toolbar: { show: false }, animations: { enabled: true } },
        series: [{ name: nameFor('copies'), data: dataFor('copies') }],
        colors,
        plotOptions: {
            bar: { borderRadius: 4, borderRadiusApplication: 'end', columnWidth: '45%', distributed: true },
        },
        xaxis: {
            categories: labels,
            labels: { style: { colors: '#98a2b3', fontSize: '11px', fontFamily: FONT } },
            axisBorder: { show: false },
            axisTicks: { show: false },
        },
        yaxis: {
            labels: { style: { colors: '#98a2b3', fontSize: '11px', fontFamily: FONT }, formatter: (v) => Math.round(v) },
        },
        grid: { borderColor: '#f2f4f7', strokeDashArray: 4 },
        legend: { show: false },
        dataLabels: { enabled: false },
        tooltip: { y: { formatter: (v) => String(v) } },
    });
    chart.render();

    const group = el.closest('[data-dashboard], .rounded-2xl')?.querySelector('[data-bar-toggle-group]');
    group?.querySelectorAll('[data-bar-mode]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const mode = btn.dataset.barMode;
            chart.updateSeries([{ name: nameFor(mode), data: dataFor(mode) }]);
            group.querySelectorAll('[data-bar-mode]').forEach((b) => {
                b.classList.toggle('bg-brand-500', b === btn);
                b.classList.toggle('text-white', b === btn);
                b.classList.toggle('text-gray-500', b !== btn);
            });
        });
    });
};

/**
 * Initialize every dashboard chart on the page.
 */
export const initDashboardCharts = () => {
    document.querySelectorAll('[data-donut]').forEach(donut);
    document.querySelectorAll('[data-line]').forEach(line);
    document.querySelectorAll('[data-bar]').forEach(bar);
};
