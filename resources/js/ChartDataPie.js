export function chartOptionsPie() {
    return {
        plugins: {
            legend: {
                display: true,
            },
        },
        responsive: true,
        aspectRatio: 1,
    }
}

export function setupChartDataPie(labels, documentStyle) {
    return {
        labels: [...labels],
        datasets: [
            {
                data: [0, 0],
                backgroundColor: [documentStyle.getPropertyValue('--blue-500'), documentStyle.getPropertyValue('--yellow-500'), documentStyle.getPropertyValue('--green-500')],
                hoverBackgroundColor: [documentStyle.getPropertyValue('--blue-400'), documentStyle.getPropertyValue('--yellow-400'), documentStyle.getPropertyValue('--green-400')]
            }
        ]
    };
}
