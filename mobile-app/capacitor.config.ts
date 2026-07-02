import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
    appId: 'com.tnftoday.app',
    appName: 'TNF Today',
    webDir: 'www',
    server: {
        url: 'https://tnftoday.com',
        cleartext: false,
        androidScheme: 'https',
    },
    android: {
        allowMixedContent: false,
        backgroundColor: '#FFFFFF',
    },
};

export default config;
