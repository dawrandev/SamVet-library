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

/** Render every chart on the public Statistika page. */
export const initStatisticsCharts = () => {
    document.querySelectorAll('[data-chart-bar]').forEach(bar);
    document.querySelectorAll('[data-chart-donut]').forEach(donut);
};
