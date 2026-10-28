window.fqSharePdf = async function (url, title, slotName = '') {
    if (slotName.trim()) title += ' · ' + slotName.trim();
    const dialog = document.createElement('dialog');
    dialog.style.cssText = 'max-width:460px;width:calc(100% - 40px);padding:24px;border:1px solid #c9d3df;border-radius:12px;font:16px system-ui;background:#fff;color:#17253a';
    const heading = document.createElement('h3');
    heading.textContent = 'Compartir PDF';
    const status = document.createElement('p');
    status.textContent = 'Preparando el mismo PDF de impresión…';
    const actions = document.createElement('div');
    actions.style.cssText = 'display:flex;gap:10px;flex-wrap:wrap;margin-top:20px';
    const button = (label, handler) => {
        const element = document.createElement('button');
        element.type = 'button';
        element.textContent = label;
        element.style.cssText = 'padding:9px 14px;border:1px solid #173d7c;border-radius:8px;background:#173d7c;color:white;cursor:pointer';
        element.addEventListener('click', handler);
        actions.appendChild(element);
        return element;
    };
    let objectUrl;
    button('Cerrar', () => dialog.close());
    dialog.append(heading, status, actions);
    document.body.appendChild(dialog);
    dialog.addEventListener('close', () => {
        dialog.remove();
        if (objectUrl) setTimeout(() => URL.revokeObjectURL(objectUrl), 60000);
    });
    dialog.showModal();
    try {
        const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store' });
        if (!response.ok || !response.headers.get('content-type')?.includes('application/pdf')) {
            throw new Error('No fue posible generar el PDF. Cerrá esta ventana e intentá nuevamente.');
        }
        const blob = await response.blob();
        if (!dialog.isConnected) return;
        const filename = (title.replace(/[\\/:*?"<>|]/g, '-').trim() || 'torneo') + '.pdf';
        const file = new File([blob], filename, { type: 'application/pdf' });
        objectUrl = URL.createObjectURL(file);
        button('Descargar PDF', () => {
            const download = document.createElement('a');
            download.href = objectUrl;
            download.download = filename;
            document.body.appendChild(download);
            download.click();
            download.remove();
            status.textContent = 'PDF descargado. En WhatsApp, abrí el chat y adjuntalo como Documento.';
        });
        if (navigator.canShare?.({ files: [file] }) && navigator.share) {
            status.textContent = 'PDF listo. Elegí una aplicación para enviarlo. Si WhatsApp no aparece, descargá el PDF y adjuntalo como Documento en el chat.';
            button('Elegir aplicación', async () => {
                try {
                    // A fresh click preserves the activation required to share files.
                    await navigator.share({ files: [file], title });
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        status.textContent = 'No se pudo compartir el archivo. Usá Descargar PDF y adjuntalo como Documento en WhatsApp.';
                    }
                }
            });
        } else {
            status.textContent = 'Este navegador no permite enviar archivos a otras aplicaciones. Descargá el PDF y, en WhatsApp, adjuntalo como Documento en el chat.';
        }
    } catch (error) {
        status.textContent = error.message || 'No fue posible preparar el PDF. Intentá nuevamente.';
    }
};
