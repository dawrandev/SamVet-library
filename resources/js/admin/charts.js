import ApexCharts from 'apexcharts';

/**
 * Bar chart — oylik statistika (masalan kitob ko'rishlari).
 * Container: #chartOne
 */
const barChart = () => {
    const el = document.querySelector('#chartOne');
    if (!el) return;

    new ApexCharts(el, {
        series: [{ name: 'Ko‘rishlar', data: [168, 385, 201, 298, 187, 195, 291, 110, 215, 390, 280, 112] }],
        colors: ['#465fff'],
        chart: { fontFamily: 'Outfit, sans-serif', type: 'bar', height: 180, toolbar: { show: false } },
        plotOptions: { bar: { horizontal: false, columnWidth: '39%', borderRadius: 5, borderRadiusApplication: 'end' } },
        dataLabels: { enabled: false },
        stroke: { show: true, width: 4, colors: ['transparent'] },
        xaxis: {
            categories: ['Yan', 'Fev', 'Mar', 'Apr', 'May', 'Iyn', 'Iyl', 'Avg', 'Sen', 'Okt', 'Noy', 'Dek'],
            axisBorder: { show: false },
            axisTicks: { show: false },
        },
        legend: { show: true, position: 'top', horizontalAlign: 'left', fontFamily: 'Outfit', markers: { radius: 99 } },
        yaxis: { title: false },
        grid: { yaxis: { lines: { show: true } } },
        fill: { opacity: 1 },
        tooltip: { x: { show: false }, y: { formatter: (val) => val } },
    }).render();
};

/**
 * Radial chart — maqsad/progress ko'rsatkichi.
 * Container: #chartTwo
 */
const radialChart = () => {
    const el = document.querySelector('#chartTwo');
    if (!el) return;

    new ApexCharts(el, {
        series: [75.55],
        colors: ['#465FFF'],
        chart: { fontFamily: 'Outfit, sans-serif', type: 'radialBar', height: 330, sparkline: { enabled: true } },
        plotOptions: {
            radialBar: {
                startAngle: -90,
                endAngle: 90,
                hollow: { size: '80%' },
                track: { background: '#E4E7EC', strokeWidth: '100%', margin: 5 },
                dataLabels: {
                    name: { show: false },
                    value: { fontSize: '36px', fontWeight: '600', offsetY: 60, color: '#1D2939', formatter: (val) => val + '%' },
                },
            },
        },
        fill: { type: 'solid', colors: ['#465FFF'] },
        stroke: { lineCap: 'round' },
        labels: ['Progress'],
    }).render();
};

/**
 * Area chart — statistika (ko'rishlar / yuklamalar).
 * Container: #chartThree
 */
const areaChart = () => {
    const el = document.querySelector('#chartThree');
    if (!el) return;

    new ApexCharts(el, {
        series: [
            { name: 'Ko‘rishlar', data: [180, 190, 170, 160, 175, 165, 170, 205, 230, 210, 240, 235] },
            { name: 'O‘qishlar', data: [40, 30, 50, 40, 55, 40, 70, 100, 110, 120, 150, 140] },
        ],
        legend: { show: false, position: 'top', horizontalAlign: 'left' },
        colors: ['#465FFF', '#9CB9FF'],
        chart: { fontFamily: 'Outfit, sans-serif', height: 310, type: 'area', toolbar: { show: false } },
        fill: { gradient: { enabled: true, opacityFrom: 0.55, opacityTo: 0 } },
        stroke: { curve: 'straight', width: ['2', '2'] },
        markers: { size: 0 },
        grid: { xaxis: { lines: { show: false } }, yaxis: { lines: { show: true } } },
        dataLabels: { enabled: false },
        tooltip: { x: { format: 'dd MMM yyyy' } },
        xaxis: {
            type: 'category',
            categories: ['Yan', 'Fev', 'Mar', 'Apr', 'May', 'Iyn', 'Iyl', 'Avg', 'Sen', 'Okt', 'Noy', 'Dek'],
            axisBorder: { show: false },
            axisTicks: { show: false },
            tooltip: false,
        },
        yaxis: { title: { style: { fontSize: '0px' } } },
    }).render();
};

/**
 * Barcha dashboard grafiklarini ishga tushirish.
 */
export const initCharts = () => {
    barChart();
    radialChart();
    areaChart();
};
