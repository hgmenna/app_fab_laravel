
<!-- Footer diseñado para Hernán Gabriel Menna -->
<footer style="width: 100%; background-color: gray; border-top: 2px solid #e5e7eb; padding: 25px 0; margin-top: 20px; font-family: Arial, sans-serif;">
    <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
        <tr>
            <!-- Columna Izquierda: Identidad -->
            <td style="width: 33%; vertical-align: middle; padding-left: clamp(10px, 5vw, 40px); text-align: left;">
                <div style="text-transform: uppercase; font-size: clamp(0.75rem, 3vw, 1rem); font-weight: bold; color: white; letter-spacing: 1px; margin-bottom: 5px; overflow-wrap: break-word;">
                    Desarrollo y Arquitectura de Software
                </div>

                <div style="font-size: clamp(1.1rem, 4vw, 1.6rem); font-weight: 900; color: white; margin-bottom: 2px; overflow-wrap: break-word;">
                    Hernán Gabriel Menna
                </div>
            </td>

            <!-- Columna Central: Contacto -->
            <td style="width: 34%; vertical-align: middle; text-align: center; color: white; font-size: clamp(0.8rem, 2.5vw, 0.95rem);">
                <div style="margin-bottom: 8px;">
                    <span style="margin-right: 15px;">
                        <strong style="color: #d97706;">📞</strong> +54 9 341 598 8191
                    </span>
                    <span>
                        <strong style="color: #d97706;">✉️</strong> hgmenna@hotmail.com
                    </span>
                </div>
                <div style="font-weight: 500;">
                    <strong style="color: #d97706;">📍</strong> Rosario - Santa Fe - Argentina
                </div>
            </td>

            <!-- Columna Derecha: Sistema -->
            <td style="width: 33%; vertical-align: middle; padding-right: clamp(10px, 5vw, 40px); text-align: right;">
                <div style="display: inline-block; background-color: #f3f4f6; padding: 4px 12px; border-radius: 20px; font-family: monospace; font-size: clamp(0.75rem, 2.5vw, 0.9rem); color: black; margin-bottom: 10px;">
                    Version {{ config('app.version') }}
                </div>

                <div style="font-size: clamp(0.7rem, 2vw, 0.9rem); color: #9ca3af; font-style: italic;">
                    <span style="color: white; font-weight: 600;">Sistema Administrativo Federacion Argentina de Billar</span>
                </div>
            </td>
        </tr>
    </table>
</footer>