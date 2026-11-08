function finalistPortraits(match, photoUrl = value => value) {
    if ((!match.final_del_torneo && !match.semifinal_del_torneo) || (!match.jugador_1 && !match.jugador_2)) return '';

    const portraits = [
        [match.jugador_1, match.foto_1],
        [match.jugador_2, match.foto_2],
    ].map(([player, photo], index) => {
        const name = player || 'A designar';
        const initials = player ? player.trim().split(/[\s,]+/).slice(0, 2).map(part => part.charAt(0)).join('') : '—';
        const image = photo ? `<img src="${esc(photoUrl(photo))}" alt="" onerror="this.hidden=true">` : '';

        return `<figure class="finalist-card ${index === 0 ? 'is-top' : 'is-bottom'}"><div class="finalist-portrait" aria-hidden="true">${esc(initials)}${image}</div><figcaption>${esc(name)}</figcaption></figure>`;
    });

    return portraits.join('');
}
