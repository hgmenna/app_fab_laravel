window.fqSharePdf = async function (url, title) {
    let objectUrl;
    try {
        const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store' });
        if (!response.ok || !response.headers.get('content-type')?.includes('application/pdf')) {
            throw new Error('No fue posible generar el PDF. Intentá nuevamente.');
        }
        const blob = await response.blob();
        const filename = (title.replace(/[\\/:*?"<>|]/g, '-').trim() || 'torneo') + '.pdf';
        const file = new File([blob], filename, { type: 'application/pdf' });
        if (navigator.canShare?.({ files: [file] }) && navigator.share) {
            await navigator.share({ files: [file], title });
            return;
        }
        objectUrl = URL.createObjectURL(file);
        const download = document.createElement('a');
        download.href = objectUrl;
        download.download = filename;
        document.body.appendChild(download);
        download.click();
        download.remove();
        alert('El PDF fue descargado. Podés adjuntar ese archivo para compartirlo.');
    } catch (error) {
        if (error.name !== 'AbortError') {
            alert(error.message || 'No fue posible compartir el PDF. Intentá nuevamente.');
        }
    } finally {
        if (objectUrl) setTimeout(() => URL.revokeObjectURL(objectUrl), 60000);
    }
};
