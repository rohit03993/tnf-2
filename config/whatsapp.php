<?php

return [
    'batch_size' => (int) env('WHATSAPP_CAMPAIGN_BATCH_SIZE', 10),
    'next_batch_delay_seconds' => (int) env('WHATSAPP_CAMPAIGN_BATCH_DELAY', 2),
    'inline_campaign_recipient_limit' => (int) env('WHATSAPP_INLINE_CAMPAIGN_LIMIT', 50),
];
