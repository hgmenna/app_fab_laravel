<span
    style="display: inline-block"
    x-data="{
        observer: null,
        init() {
            const title = this.$el;
            const heading = title.parentElement;
            const fit = () => {
                title.style.fontSize = '';
                const size = parseFloat(getComputedStyle(title).fontSize);
                if (title.offsetWidth > heading.clientWidth) {
                    title.style.fontSize = Math.max(14, Math.floor(size * heading.clientWidth / title.offsetWidth)) + 'px';
                }
                heading.tabIndex = title.offsetWidth > heading.clientWidth ? 0 : -1;
            };
            this.observer = new ResizeObserver(fit);
            this.observer.observe(heading);
            document.fonts.ready.then(fit);
        },
        destroy() {
            this.observer.disconnect();
        }
    }"
>{{ $title }}</span>
