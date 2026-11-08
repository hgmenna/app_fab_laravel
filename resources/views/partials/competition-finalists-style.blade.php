.finalist-anchor{position:relative}
.finalist-card{position:absolute;z-index:1;left:0;right:0;min-width:0;margin:0;text-align:center}
.finalist-card.is-top{bottom:calc(100% + var(--finalist-top-gap,10px))}
.finalist-card.is-bottom{top:calc(100% + 10px)}
.finalist-portrait{position:relative;display:grid;place-items:center;width:min(100%,var(--finalist-photo-size,84px));aspect-ratio:1;margin:auto;overflow:hidden;border-radius:14px;background:transparent;color:#294765;font-size:1rem;font-weight:800}
.finalist-portrait img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center 35%}
.finalist-portrait:has(img:not([hidden])) > span{visibility:hidden}
