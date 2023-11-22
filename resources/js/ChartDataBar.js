export function chartOptionsBar() {
    return {
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            },
        },
        responsive: true,
    }
}

export function setupChartDataBar(labels, label) {
    return {
        labels: [...labels],
        datasets: [
            {
                label: label,
                data: [],
                backgroundColor: ['rgba(255, 159, 64, 0.2)', 'rgba(75, 192, 192, 0.2)', 'rgba(54, 162, 235, 0.2)', 'rgba(153, 102, 255, 0.2)', 'rgba(255, 0, 0, 0.2)', 'rgba(0, 204, 153, 0.2)', 'rgba(51, 102, 204, 0.2)'],
                borderColor: ['rgb(255, 159, 64)', 'rgb(75, 192, 192)', 'rgb(54, 162, 235)', 'rgb(153, 102, 255)', 'rgba(255, 0, 0, 0.2)', 'rgba(0, 204, 153, 0.2)', 'rgba(51, 102, 204, 0.2)'],
                borderWidth: 1
            }
        ]
    }
}
