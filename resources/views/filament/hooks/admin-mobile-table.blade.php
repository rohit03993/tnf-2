<style>
    /* ── Prevent horizontal scroll across admin ── */
    .fi-body,
    .fi-main-ctn,
    .fi-main,
    .fi-page,
    .fi-page-main,
    .fi-wi-widget {
        max-width: 100%;
    }

    .fi-page-main {
        overflow-x: clip;
    }

    /* ── Dashboard: compact welcome card on mobile ── */
    @media (max-width: 767px) {
        .fi-wi-account {
            border-radius: 1rem !important;
        }

        .fi-wi-account .fi-section-content-ctn {
            padding: 0.85rem 1rem !important;
        }

        .fi-wi-account .fi-section-content-ctn > div {
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .fi-wi-account .fi-wi-account-avatar {
            width: 2.75rem !important;
            height: 2.75rem !important;
        }

        .fi-wi-account .fi-wi-account-heading {
            font-size: 1rem !important;
            line-height: 1.3;
        }
    }

    /* ── Dashboard: stat cards ── */
    @media (max-width: 767px) {
        .fi-wi-stats-overview .fi-section-content-ctn {
            padding: 0 !important;
        }

        .fi-wi-stats-overview .fi-sc.fi-grid > .fi-grid-col {
            min-width: 0 !important;
        }

        .fi-wi-stats-overview-stat {
            border-radius: 1rem !important;
            border: 1px solid rgb(228 231 236) !important;
            box-shadow: 0 1px 2px rgba(15, 19, 32, 0.04) !important;
            padding: 0.85rem !important;
            min-height: 5.5rem;
        }

        .fi-wi-stats-overview-stat-value {
            font-size: 1.35rem !important;
            line-height: 1.2 !important;
        }

        .fi-wi-stats-overview-stat-label {
            font-size: 0.72rem !important;
            line-height: 1.25 !important;
        }
    }

    /* ── Custom card widgets (dashboard) ── */
    .tnf-admin-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
    }

    @media (min-width: 768px) {
        .tnf-admin-actions {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }

    .tnf-admin-action {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.65rem;
        min-height: 5.5rem;
        padding: 0.9rem 1rem;
        border-radius: 1rem;
        border: 1px solid rgb(228 231 236);
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 19, 32, 0.04);
        text-decoration: none;
        color: rgb(17 24 39);
        transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
    }

    .tnf-admin-action:hover {
        border-color: rgb(188 30 56 / 0.35);
        box-shadow: 0 4px 14px rgba(188, 30, 56, 0.08);
        transform: translateY(-1px);
    }

    .tnf-admin-action--primary {
        background: linear-gradient(145deg, #bc1e38 0%, #9a1830 100%);
        border-color: #bc1e38;
        color: #fff;
    }

    .tnf-admin-action--primary:hover {
        border-color: #9a1830;
        color: #fff;
        box-shadow: 0 6px 18px rgba(188, 30, 56, 0.25);
    }

    .tnf-admin-action--warning {
        border-color: rgb(245 158 11 / 0.45);
        background: rgb(255 251 235);
    }

    .tnf-admin-action__icon {
        display: inline-flex;
        width: 1.35rem;
        height: 1.35rem;
        opacity: 0.92;
    }

    .tnf-admin-action__icon svg {
        width: 100%;
        height: 100%;
    }

    .tnf-admin-action__label {
        font-size: 0.82rem;
        font-weight: 600;
        line-height: 1.3;
    }

    .tnf-admin-card-list {
        display: grid;
        gap: 0.75rem;
    }

    .tnf-admin-card {
        display: grid;
        grid-template-columns: 4.25rem minmax(0, 1fr);
        gap: 0.75rem;
        align-items: start;
        padding: 0.85rem;
        border: 1px solid rgb(228 231 236);
        border-radius: 1rem;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 19, 32, 0.04);
        text-decoration: none;
        color: inherit;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .tnf-admin-card:hover {
        border-color: rgb(188 30 56 / 0.35);
        box-shadow: 0 4px 14px rgba(188, 30, 56, 0.08);
    }

    .tnf-admin-card--team {
        grid-template-columns: minmax(0, 1fr);
    }

    .tnf-admin-card__media img {
        width: 4.25rem;
        height: 4.25rem;
        border-radius: 0.75rem;
        object-fit: cover;
        display: block;
        background: rgb(243 244 246);
    }

    .tnf-admin-card__title {
        margin: 0;
        font-size: 0.92rem;
        font-weight: 600;
        line-height: 1.35;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .tnf-admin-card__meta {
        margin: 0.25rem 0 0;
        font-size: 0.78rem;
        line-height: 1.35;
        color: rgb(107 114 128);
    }

    .tnf-admin-card__date {
        margin: 0.2rem 0 0;
        font-size: 0.72rem;
        color: rgb(156 163 175);
    }

    .tnf-admin-badge {
        font-weight: 600;
    }

    .tnf-admin-badge--published { color: rgb(22 163 74); }
    .tnf-admin-badge--pending { color: rgb(217 119 6); }
    .tnf-admin-badge--draft { color: rgb(107 114 128); }

    .tnf-admin-team-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem 0.75rem;
        margin-top: 0.45rem;
    }

    .tnf-admin-team-stat {
        font-size: 0.78rem;
        color: rgb(107 114 128);
        padding: 0.25rem 0.55rem;
        border-radius: 999px;
        background: rgb(249 250 251);
        border: 1px solid rgb(243 244 246);
    }

    .tnf-admin-team-stat strong {
        color: rgb(17 24 39);
        font-weight: 700;
    }

    .tnf-admin-team-stat--pending {
        background: rgb(255 251 235);
        border-color: rgb(254 243 199);
        color: rgb(180 83 9);
    }

    .tnf-admin-empty {
        margin: 0;
        padding: 1rem;
        text-align: center;
        font-size: 0.875rem;
        color: rgb(107 114 128);
        border: 1px dashed rgb(209 213 219);
        border-radius: 0.85rem;
        background: rgb(249 250 251);
    }

    .tnf-admin-section-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        width: 100%;
        flex-wrap: wrap;
    }

    .tnf-admin-view-all {
        margin-top: 0.85rem;
    }

    .tnf-admin-view-all .fi-btn {
        width: 100%;
        justify-content: center;
    }

    /* ── Dashboard widget sections ── */
    @media (max-width: 767px) {
        .fi-wi-widget .fi-section {
            border-radius: 1rem !important;
        }

        .fi-wi-widget .fi-section-header {
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .fi-wi-widget .fi-section-header-heading {
            font-size: 1rem !important;
        }

        .fi-page-header {
            padding-bottom: 0.25rem;
        }

        .fi-page-header-heading {
            font-size: 1.35rem !important;
        }

        .fi-page-main {
            gap: 0.85rem !important;
        }
    }

    /* ── Resource tables → card layout on mobile ── */
    @media (max-width: 767px) {
        .fi-main .fi-ta-ctn {
            overflow-x: visible !important;
        }

        .fi-main .fi-ta-content {
            overflow-x: visible !important;
        }

        .fi-main .fi-ta-table {
            display: block !important;
            width: 100% !important;
            min-width: 0 !important;
        }

        .fi-main .fi-ta-table thead {
            display: none;
        }

        .fi-main .fi-ta-table tbody {
            display: grid;
            gap: 0.75rem;
            padding: 0.25rem 0 0.75rem;
        }

        .fi-main .fi-ta-row {
            display: grid !important;
            grid-template-columns: 4.25rem minmax(0, 1fr);
            grid-template-areas:
                'thumb title'
                'thumb meta'
                'thumb actions';
            gap: 0.35rem 0.75rem;
            align-items: start;
            padding: 0.85rem !important;
            border: 1px solid rgb(228 231 236);
            border-radius: 0.9rem;
            background: #fff;
            box-shadow: 0 1px 2px rgba(15, 19, 32, 0.04);
            width: 100% !important;
        }

        .fi-main .fi-ta-cell {
            display: block !important;
            padding: 0 !important;
            border: 0 !important;
            width: auto !important;
            min-width: 0 !important;
            white-space: normal !important;
        }

        .fi-main .fi-ta-cell:first-child {
            grid-area: thumb;
        }

        .fi-main .fi-ta-cell:nth-child(2) {
            grid-area: title;
        }

        .fi-main .fi-ta-cell:nth-child(3) {
            grid-area: meta;
        }

        .fi-main .fi-ta-cell:last-child {
            grid-area: actions;
            margin-top: 0.15rem;
        }

        .fi-main .fi-ta-cell .fi-ta-image img {
            width: 4.25rem !important;
            height: 4.25rem !important;
            border-radius: 0.65rem;
            object-fit: cover;
        }

        .fi-main .fi-ta-cell .fi-ta-text-item {
            font-size: 0.95rem;
            line-height: 1.35;
            font-weight: 600;
        }

        .fi-main .fi-ta-cell .fi-ta-text-item-description {
            margin-top: 0.15rem;
            font-size: 0.78rem;
            line-height: 1.3;
        }

        .fi-main .fi-ta-actions-cell .fi-ac-actions {
            flex-wrap: wrap;
            gap: 0.35rem;
        }

        .fi-main .fi-ta-actions-cell .fi-ac-btn-action {
            min-height: 2.35rem;
        }

        .fi-main .fi-ta-header-toolbar {
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .fi-main .fi-ta-header-toolbar .fi-input-wrp {
            width: 100%;
        }
    }
</style>
