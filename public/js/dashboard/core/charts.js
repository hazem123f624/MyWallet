const css = (name) => getComputedStyle(document.documentElement).getPropertyValue(name).trim();

const palette = () => ({
    text: css('--dash-text'),
    textSoft: css('--dash-text-soft'),
    muted: css('--dash-muted'),
    border: css('--dash-border'),
    primary: css('--dash-primary'),
    success: css('--dash-success'),
    warning: css('--dash-warning'),
    statusActive: css('--dash-chart-active') || css('--dash-success'),
    statusSuspended: css('--dash-chart-suspended') || css('--dash-warning'),
    danger: css('--dash-danger'),
    surface: css('--dash-surface'),
});

const destroy = (canvas) => {
    if (canvas?.__dashChart) {
        canvas.__dashChart.destroy();
        canvas.__dashChart = null;
    }
};

const formatDay = (value) => {
    const date = new Date(`${value}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric' }).format(date);
};

export const renderLineChart = (canvasId, labels, values, label = 'Users') => {
    const canvas = document.getElementById(canvasId);

    if (!canvas || typeof window.Chart === 'undefined') {
        return false;
    }

    destroy(canvas);
    const colors = palette();
    const context = canvas.getContext('2d');
    const gradient = context.createLinearGradient(0, 0, 0, 250);
    gradient.addColorStop(0, `${colors.primary}4d`);
    gradient.addColorStop(0.65, `${colors.primary}18`);
    gradient.addColorStop(1, `${colors.primary}00`);

    canvas.__dashChart = new window.Chart(canvas, {
        type: 'line',
        data: {
            labels: labels.map(formatDay),
            datasets: [{
                label,
                data: values,
                borderColor: colors.primary,
                backgroundColor: gradient,
                fill: true,
                borderWidth: 2.5,
                pointRadius: 0,
                pointHoverRadius: 5,
                pointHoverBorderWidth: 2,
                pointHoverBackgroundColor: colors.surface,
                pointHoverBorderColor: colors.primary,
                tension: 0.42,
                cubicInterpolationMode: 'monotone',
                spanGaps: true,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: colors.surface,
                    titleColor: colors.text,
                    bodyColor: colors.text,
                    borderColor: colors.border,
                    borderWidth: 1,
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: {
                        color: colors.textSoft,
                        maxTicksLimit: 7,
                        autoSkip: true,
                        maxRotation: 0,
                        font: { size: 11, weight: '600' },
                    },
                    title: {
                        display: true,
                        text: 'Date',
                        color: colors.muted,
                        font: { size: 10, weight: '700' },
                    },
                },
                y: {
                    beginAtZero: true,
                    border: { display: false },
                    ticks: {
                        color: colors.textSoft,
                        precision: 0,
                        font: { size: 11, weight: '600' },
                        padding: 6,
                    },
                    grid: { color: colors.border, drawTicks: false },
                    title: {
                        display: true,
                        text: 'New users',
                        color: colors.muted,
                        font: { size: 10, weight: '700' },
                    },
                },
            },
        },
    });

    return true;
};

export const renderStatusDoughnut = (canvasId, labels, values) => {
    const canvas = document.getElementById(canvasId);

    if (!canvas || typeof window.Chart === 'undefined') {
        return false;
    }

    destroy(canvas);
    const colors = palette();
    const total = values.reduce((sum, value) => sum + Number(value || 0), 0);

    canvas.__dashChart = new window.Chart(canvas, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: [colors.statusActive, colors.statusSuspended],
                borderColor: colors.surface,
                borderWidth: 2,
                borderRadius: 14,
                spacing: 2,
                hoverOffset: 5,
                hoverBorderWidth: 2,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '74%',
            layout: {
                padding: 4,
            },
            animation: {
                duration: 650,
                easing: 'easeOutQuart',
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: colors.surface,
                    titleColor: colors.text,
                    bodyColor: colors.text,
                    borderColor: colors.border,
                    borderWidth: 1,
                    padding: 10,
                    displayColors: true,
                    boxWidth: 8,
                    boxHeight: 8,
                    usePointStyle: true,
                    callbacks: {
                        label: (context) => {
                            const value = Number(context.parsed || 0);
                            const percentage = total > 0 ? Math.round((value / total) * 100) : 0;

                            return ` ${context.label}: ${value.toLocaleString()} (${percentage}%)`;
                        },
                    },
                },
            },
            onHover: (event, elements) => {
                if (event?.native?.target) {
                    event.native.target.style.cursor = elements.length > 0 ? 'pointer' : 'default';
                }
            },
        },
    });

    return true;
};

export const renderDistributionDoughnut = (canvasId, labels, values, colors = []) => {
    const canvas = document.getElementById(canvasId);

    if (!canvas || typeof window.Chart === 'undefined') {
        return false;
    }

    destroy(canvas);
    const paletteColors = palette();
    const total = values.reduce((sum, value) => sum + Number(value || 0), 0);

    canvas.__dashChart = new window.Chart(canvas, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: colors.length ? colors : [paletteColors.primary, paletteColors.success, paletteColors.warning, paletteColors.danger],
                borderColor: paletteColors.surface,
                borderWidth: 2,
                borderRadius: 10,
                spacing: 2,
                hoverOffset: 5,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            animation: { duration: 650, easing: 'easeOutQuart' },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: paletteColors.textSoft,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        boxWidth: 8,
                        padding: 12,
                        font: { size: 10 },
                    },
                },
                tooltip: {
                    backgroundColor: paletteColors.surface,
                    titleColor: paletteColors.text,
                    bodyColor: paletteColors.text,
                    borderColor: paletteColors.border,
                    borderWidth: 1,
                    callbacks: {
                        label: (context) => {
                            const value = Number(context.parsed || 0);
                            const percentage = total > 0 ? Math.round((value / total) * 100) : 0;

                            return ` ${context.label}: ${value.toLocaleString()} (${percentage}%)`;
                        },
                    },
                },
            },
        },
    });

    return true;
};
