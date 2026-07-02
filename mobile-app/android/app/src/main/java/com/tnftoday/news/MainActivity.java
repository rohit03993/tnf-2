package com.tnftoday.news;

import android.webkit.WebView;
import com.getcapacitor.BridgeActivity;

public class MainActivity extends BridgeActivity {

    @Override
    public void onStart() {
        super.onStart();
        WebView webView = this.bridge.getWebView();
        if (webView != null) {
            String userAgent = webView.getSettings().getUserAgentString();
            if (!userAgent.contains("TNFTodayCapacitor")) {
                webView.getSettings().setUserAgentString(userAgent + " TNFTodayCapacitor/1.0");
            }
        }
    }
}
