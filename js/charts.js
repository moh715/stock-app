function buildChartData(timeSeries) {
    if (!timeSeries?.values) return null;

    const values = [...timeSeries.values].reverse();
    const labels = values.map(v => v.datetime);
    const prices = values.map(v => parseFloat(v.close));
    return { labels, prices };
}

function getChartColor(prices) {
    if (!prices || prices.length < 2) return { line: 'royalblue', fill: 'rgba(65,105,225,0.1)' };
    return prices[prices.length - 1] >= prices[0]
        ? { line: 'seagreen', fill: 'rgba(46,139,87,0.1)'  }
        : { line: 'crimson',  fill: 'rgba(220,20,60,0.1)'  };
}

function createSmallChart(canvas, labels, prices) {
    const { line, fill } = getChartColor(prices);

    return new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                data: prices,
                borderColor: line,
                backgroundColor: fill,
                borderWidth: 1.5,
                pointRadius: 0,
                fill: true,
                tension: 0.3,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
            scales:  { x: { display: false }, y: { display: false } },
            animation: false,
        },
    });
}

function createLargeChart(canvas, labels, prices) {
    const { line, fill } = getChartColor(prices);

    return new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Close Price',
                data: prices,
                borderColor: line,
                backgroundColor: fill,
                borderWidth: 2,
                pointRadius: 3,
                pointHoverRadius: 5,
                fill: true,
                tension: 0.3,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ` $${ctx.parsed.y.toFixed(2)}`,
                    },
                },
            },
            scales: {
                x: {
                    ticks: { maxTicksLimit: 8, maxRotation: 0 },
                    grid:  { display: false },
                },
                y: {
                    ticks: { callback: v => '$' + v.toFixed(0) },
                    grid:  { color: 'rgba(128,128,128,0.1)' },
                },
            },
        },
    });
}