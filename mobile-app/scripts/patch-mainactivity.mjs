import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const androidJavaDir = path.join(__dirname, '..', 'android', 'app', 'src', 'main', 'java');

function findMainActivityFile(dir) {
    if (!fs.existsSync(dir)) {
        return null;
    }

    for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
        const fullPath = path.join(dir, entry.name);

        if (entry.isDirectory()) {
            const nested = findMainActivityFile(fullPath);
            if (nested) {
                return nested;
            }
        } else if (entry.name === 'MainActivity.java') {
            return fullPath;
        }
    }

    return null;
}

const mainActivityPath = findMainActivityFile(androidJavaDir);

if (!mainActivityPath) {
    console.error('MainActivity.java not found. Run: npm install && npx cap add android');
    process.exit(1);
}

let source = fs.readFileSync(mainActivityPath, 'utf8');

if (source.includes('TNFTodayCapacitor/1.0')) {
    console.log('MainActivity already patched.');
    process.exit(0);
}

const imports = `import android.webkit.WebView;
`;

if (!source.includes('import android.webkit.WebView;')) {
    source = source.replace(
        /(package [^;]+;\s*\n)/,
        `$1\n${imports}`,
    );
}

const patch = `
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
`;

if (!source.includes('public void onStart()')) {
    source = source.replace(
        /public class MainActivity extends BridgeActivity(?:\s*\{\s*\})?/,
        `public class MainActivity extends BridgeActivity {${patch}`,
    );
}

fs.writeFileSync(mainActivityPath, source);
console.log('Patched MainActivity user-agent:', mainActivityPath);
