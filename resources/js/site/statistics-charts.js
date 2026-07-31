import ApexCharts from 'apexcharts';

const FONT = 'Outfit Variable, Outfit, sans-serif';

/**
 * Horizontal bar chart for a label => count breakdown (category/format/type/
 * age-group names can run long — horizontal avoids rotated x-axis labels).
 * Data via data-* attributes: data-labels, data-series, data-colors.
 */
const bar = (el) => {
    const labels = JSON.parse(el.dataset.labels || '[]');
    const series = JSON.parse(el.dataset.series || '[]');
    const colors = JSON.parse(el.dataset.colors || '[]');

    new ApexCharts(el, {
        chart: { type: 'bar', height: Math.max(180, labels.length * 44), fontFamily: FONT, toolbar: { show: false }, animations: { enabled: true } },
        series: [{ name: el.dataset.seriesName || '', data: series }],
        colors,
        plotOptions: {
            bar: { borderRadius: 4, borderRadiusApplication: 'end', horizontal: true, barHeight: '55%', distributed: true },
        },
        xaxis: {
            categories: labels,
            labels: { style: { colors: '#98a2b3', fontSize: '11px', fontFamily: FONT }, formatter: (v) => Math.round(v) },
            axisBorder: { show: false },
            axisTicks: { show: false },
        },
        yaxis: {
            labels: { style: { colors: '#475467', fontSize: '12px', fontFamily: FONT } },
        },
        grid: { borderColor: '#f2f4f7', strokeDashArray: 4 },
        legend: { show: false },
        dataLabels: { enabled: false },
        tooltip: { y: { formatter: (v) => String(v) } },
    }).render();
};

/**
 * Donut chart for a small label => count breakdown (few categories).
 * Data via data-* attributes: data-labels, data-series, data-colors, data-center.
 */
const donut = (el) => {
    const labels = JSON.parse(el.dataset.labels || '[]');
    const series = JSON.parse(el.dataset.series || '[]');
    const colors = JSON.parse(el.dataset.colors || '[]');
    const center = el.dataset.center || '';
    const total = series.reduce((a, b) => a + Number(b), 0);
    const empty = total === 0;

    new ApexCharts(el, {
        chart: { type: 'donut', height: 260, fontFamily: FONT, animations: { enabled: false } },
        series: empty ? [1] : series,
        labels: empty ? ['—'] : labels,
        colors: empty ? ['#e4e7ec'] : colors,
        stroke: { width: 0 },
        dataLabels: { enabled: false },
        legend: {
            position: 'bottom',
            fontSize: '12px',
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
                            fontSize: '12px',
                            color: '#98a2b3',
                            formatter: () => String(total),
                        },
                        value: { fontSize: '22px', fontWeight: 700, color: '#1d2939' },
                    },
                },
            },
        },
        tooltip: { enabled: !empty },
    }).render();
};

/**
 * Stacked bar chart for the "Yillik hisobot" (annual acquisitions report) —
 * years on the x-axis, stack segments switchable between 3 dimensions
 * (shakli/toifasi/tili) via a sibling [data-stacked-bar-dimension-group],
 * plus a nusxa/nomi toggle via [data-bar-toggle-group]. Mirrors the admin
 * dashboard's stackedBar() (resources/js/admin/charts.js) — same data
 * contract and same fixed/shared y-axis max discipline, only the button
 * active-state classes differ (site uses bg-blue-700, not bg-brand-500) to
 * match this page's own design.
 *
 * Without a fixed shared max, ApexCharts auto-scales the y-axis to
 * whichever state is currently shown — since switching dimension AND mode
 * both change which stacked totals are visible (6 states total: 3 dims x 2
 * modes), an unscoped axis would silently rescale on toggle, making a
 * shrinking value look like it grew. Computing the true worst-case stacked
 * total across all 6 states up front, once, and never touching yaxis again
 * afterward is what prevents that.
 */
const stackedBar = (el) => {
    const years = JSON.parse(el.dataset.years || '[]');
    const dims = JSON.parse(el.dataset.dimensions || '{}');

    let sharedMax = 1;
    for (const dim of Object.values(dims)) {
        for (const mode of ['copies', 'titles']) {
            years.forEach((_, yi) => {
                const stackedTotal = dim[mode].reduce((sum, series) => sum + Number(series[yi] || 0), 0);
                sharedMax = Math.max(sharedMax, stackedTotal);
            });
        }
    }
    sharedMax *= 1.1;

    const seriesFor = (dim, mode) => dims[dim].labels.map((name, i) => ({ name, data: dims[dim][mode][i] }));

    let currentDim = el.dataset.defaultDimension || Object.keys(dims)[0];
    let currentMode = 'copies';

    const chart = new ApexCharts(el, {
        chart: { type: 'bar', stacked: true, height: 320, fontFamily: FONT, toolbar: { show: false }, animations: { enabled: true } },
        series: seriesFor(currentDim, currentMode),
        colors: dims[currentDim].colors,
        plotOptions: {
            bar: { borderRadius: 4, borderRadiusApplication: 'end', columnWidth: '45%' },
        },
        xaxis: {
            categories: years,
            labels: { style: { colors: '#98a2b3', fontSize: '11px', fontFamily: FONT } },
            axisBorder: { show: false },
            axisTicks: { show: false },
        },
        yaxis: {
            min: 0,
            max: sharedMax,
            labels: { style: { colors: '#98a2b3', fontSize: '11px', fontFamily: FONT }, formatter: (v) => Math.round(v) },
        },
        grid: { borderColor: '#f2f4f7', strokeDashArray: 4 },
        legend: {
            show: true,
            position: 'top',
            horizontalAlign: 'left',
            fontSize: '12px',
            fontFamily: FONT,
            labels: { colors: '#667085' },
            markers: { radius: 12 },
            itemMargin: { horizontal: 10, vertical: 4 },
        },
        dataLabels: { enabled: false },
        tooltip: { y: { formatter: (v) => String(v) } },
    });
    chart.render();

    const redraw = () => {
        chart.updateOptions({
            series: seriesFor(currentDim, currentMode),
            colors: dims[currentDim].colors,
        }, true, true);
    };

    const card = el.closest('section');

    const setActive = (group, btn, attr) => {
        group.querySelectorAll(`[data-${attr}]`).forEach((b) => {
            b.classList.toggle('bg-blue-700', b === btn);
            b.classList.toggle('text-white', b === btn);
            b.classList.toggle('text-gray-500', b !== btn);
        });
    };

    card?.querySelectorAll('[data-stacked-bar-dimension-group] [data-stacked-bar-dimension]').forEach((btn) => {
        btn.addEventListener('click', () => {
            currentDim = btn.dataset.stackedBarDimension;
            redraw();
            setActive(btn.parentElement, btn, 'stacked-bar-dimension');
        });
    });

    card?.querySelectorAll('[data-bar-toggle-group] [data-bar-mode]').forEach((btn) => {
        btn.addEventListener('click', () => {
            currentMode = btn.dataset.barMode;
            redraw();
            setActive(btn.parentElement, btn, 'bar-mode');
        });
    });
};

/** Render every chart on the public Statistika page. */
export const initStatisticsCharts = () => {
    document.querySelectorAll('[data-chart-bar]').forEach(bar);
    document.querySelectorAll('[data-chart-donut]').forEach(donut);
    document.querySelectorAll('[data-chart-stacked-bar]').forEach(stackedBar);
};
