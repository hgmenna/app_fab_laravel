package ar.fab.deportes;

import android.app.Activity;
import android.content.ActivityNotFoundException;
import android.content.Intent;
import android.graphics.Color;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.view.View;
import android.view.WindowInsets;
import android.webkit.WebResourceRequest;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.Toast;
import android.window.OnBackInvokedDispatcher;

/** Abre las pantallas móviles de FAB y conserva su navegación y sesión. */
public final class MainActivity extends Activity {
    private WebView screen;
    private final Uri home = Uri.parse(BuildConfig.FAB_HOME);

    @Override
    protected void onCreate(Bundle state) {
        super.onCreate(state);
        screen = new WebView(this);
        screen.setBackgroundColor(Color.rgb(11, 20, 34));
        screen.getSettings().setJavaScriptEnabled(true);
        screen.getSettings().setDomStorageEnabled(true);
        screen.getSettings().setAllowFileAccess(false);
        screen.getSettings().setAllowContentAccess(false);
        screen.setWebViewClient(new WebViewClient() {
            @Override
            public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
                if (!request.isForMainFrame()) return false;
                Uri url = request.getUrl();
                boolean sameOrigin = home.getScheme().equals(url.getScheme())
                    && home.getHost().equals(url.getHost()) && home.getPort() == url.getPort();
                if (sameOrigin) return false;
                if (!"https".equals(url.getScheme())) return true;
                try {
                    startActivity(new Intent(Intent.ACTION_VIEW, url));
                } catch (ActivityNotFoundException error) {
                    Toast.makeText(MainActivity.this, "No hay una app para abrir este enlace.", Toast.LENGTH_SHORT).show();
                }
                return true;
            }
        });
        screen.setOnApplyWindowInsetsListener((view, insets) -> {
            if (Build.VERSION.SDK_INT >= 30) {
                var bars = insets.getInsets(WindowInsets.Type.systemBars());
                view.setPadding(bars.left, bars.top, bars.right, bars.bottom);
            } else {
                view.setPadding(insets.getSystemWindowInsetLeft(), insets.getSystemWindowInsetTop(),
                    insets.getSystemWindowInsetRight(), insets.getSystemWindowInsetBottom());
            }
            return insets;
        });
        setContentView(screen);
        if (Build.VERSION.SDK_INT >= 33) {
            getOnBackInvokedDispatcher().registerOnBackInvokedCallback(
                OnBackInvokedDispatcher.PRIORITY_DEFAULT, () -> {
                    if (screen.canGoBack()) screen.goBack(); else finish();
                });
        }
        if (state == null || screen.restoreState(state) == null) screen.loadUrl(home.toString());
    }

    @Override
    protected void onSaveInstanceState(Bundle state) {
        screen.saveState(state);
        super.onSaveInstanceState(state);
    }

    @android.annotation.SuppressLint("GestureBackNavigation")
    @Override
    public void onBackPressed() {
        if (screen.canGoBack()) screen.goBack();
        else super.onBackPressed();
    }
}
