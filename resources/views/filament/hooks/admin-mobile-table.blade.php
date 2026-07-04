<style>
    @media (max-width: 767px) {
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
        }

        .fi-main .fi-ta-cell {
            display: block !important;
            padding: 0 !important;
            border: 0 !important;
            width: auto !important;
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
        }
    }
</style>
