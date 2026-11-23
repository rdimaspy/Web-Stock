/**
 * Modul Laporan & Analitik (Ekspor CSV, Cetak PDF, dan Render Grafik Chart.js)
 */

// Ekspor tabel laporan (#reportTable) ke file CSV
async function exportTableToCSV(defaultFilename) {
    const cfg = window.REPORT_CONFIG || {};
    if (!cfg.hasData) {
        showToast(cfg.noDataMsg, 'error');
        return;
    }

    const table = document.getElementById('reportTable');
    const csv = Array.from(table.rows).map(row =>
        Array.from(row.cells).map(cell => `"${cell.innerText.replace(/"/g, '""')}"`).join(',')
    );
    const csvData = '\uFEFF' + csv.join('\n');

    if ('showSaveFilePicker' in window) {
        try {
            const handle = await window.showSaveFilePicker({
                suggestedName: defaultFilename,
                types: [{ description: 'CSV File', accept: { 'text/csv': ['.csv'] } }]
            });
            const writable = await handle.createWritable();
            await writable.write(csvData);
            await writable.close();
            showToast(cfg.csvSuccessMsg, 'success');
        } catch (err) {
            // Dibatalkan oleh pengguna
        }
    } else {
        try {
            const blob = new Blob([csvData], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.download = defaultFilename;
            link.href = window.URL.createObjectURL(blob);
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            showToast(cfg.csvSuccessMsg, 'success');
        } catch (e) {
            showToast(cfg.csvFailedMsg, 'error');
        }
    }
}

// Muat dan cetak laporan PDF melalui iframe tersembunyi (reports.php)
function printPDF(url) {
    const cfg = window.REPORT_CONFIG || {};
    if (!cfg.hasData) {
        showToast(cfg.noDataMsg, 'error');
        return;
    }

    const iframe = document.getElementById('pdfFrame');
    iframe.src = url;
    iframe.onload = function () {
        try {
            iframe.contentWindow.focus();
            iframe.contentWindow.addEventListener('afterprint', function handleAfterPrint() {
                showToast(cfg.pdfSuccessMsg, 'success');
                iframe.contentWindow.removeEventListener('afterprint', handleAfterPrint);
            });
            iframe.contentWindow.print();
        } catch (e) {
            console.error(e);
            showToast(cfg.pdfFailedMsg, 'error');
        }
    };
}

// Cetak halaman grafik analitik ke PDF (reports-analytics.php)
function exportReportToPDF() {
    const cfg = window.REPORT_CONFIG || {};
    if (!cfg.hasData) {
        showToast(cfg.noDataMsg, 'error');
        return;
    }

    window.addEventListener('afterprint', function handleAfterPrint() {
        showToast(cfg.pdfSuccessMsg, 'success');
        window.removeEventListener('afterprint', handleAfterPrint);
    });
    window.print();
}

// Inisialisasi grafik Chart.js jika berada di halaman analitik
function initReportCharts() {
    const cfg = window.REPORT_CONFIG;
    if (!cfg || !cfg.chartData || typeof Chart === 'undefined') return;

    const { labels, stockIn, stockOut, profit, stockInLabel, stockOutLabel, profitLabel } = cfg.chartData;
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: true } }
    };

    const stockCanvas = document.getElementById('stockMovementChart');
    if (stockCanvas) {
        new Chart(stockCanvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    { label: stockInLabel, data: stockIn, backgroundColor: '#76553D' },
                    { label: stockOutLabel, data: stockOut, backgroundColor: '#B99A7D' }
                ]
            },
            options: commonOptions
        });
    }

    const profitCanvas = document.getElementById('profitTrendChart');
    if (profitCanvas) {
        new Chart(profitCanvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    { label: profitLabel, data: profit, backgroundColor: '#2E7D32' }
                ]
            },
            options: commonOptions
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initReportCharts);
} else {
    initReportCharts();
}
