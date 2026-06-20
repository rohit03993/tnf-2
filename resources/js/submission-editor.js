import { Editor } from '@tiptap/core';
import GapCursor from '@tiptap/extension-gapcursor';
import Image from '@tiptap/extension-image';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import Subscript from '@tiptap/extension-subscript';
import Superscript from '@tiptap/extension-superscript';
import Table from '@tiptap/extension-table';
import TableCell from '@tiptap/extension-table-cell';
import TableHeader from '@tiptap/extension-table-header';
import TableRow from '@tiptap/extension-table-row';
import TextAlign from '@tiptap/extension-text-align';
import Underline from '@tiptap/extension-underline';
import StarterKit from '@tiptap/starter-kit';

const ICONS = {
    bold: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M7 5h6a3.5 3.5 0 0 1 0 7H7V5zm0 7h7a3.5 3.5 0 0 1 0 7H7v-7z"/></svg>',
    italic: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 5h6M9 19h6M14 5l-4 14"/></svg>',
    underline: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 5v6a5 5 0 0 0 10 0V5M5 19h14"/></svg>',
    strike: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 8h12M8 12h8M7 16h10"/></svg>',
    subscript: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m4 19 8-8M4 19h4M12 11V7a2 2 0 0 1 4 0v1"/><path d="M16 11h4"/></svg>',
    superscript: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m4 19 8-8M4 19h4M12 5v4a2 2 0 0 0 4 0V7"/><path d="M16 5h4"/></svg>',
    clearFormat: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12h10l1-12"/></svg>',
    h2: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6v12M12 6v12M4 12h8M16 8h6M16 12h6M16 16h6"/></svg>',
    h3: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6v12M12 6v12M4 12h8M16 10h6M16 14h4"/></svg>',
    alignLeft: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h10M4 18h14"/></svg>',
    alignCenter: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M7 12h10M5 18h14"/></svg>',
    alignRight: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M10 12h10M6 18h14"/></svg>',
    bulletList: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6h12M9 12h12M9 18h12"/><circle cx="4" cy="6" r="1.5" fill="currentColor"/><circle cx="4" cy="12" r="1.5" fill="currentColor"/><circle cx="4" cy="18" r="1.5" fill="currentColor"/></svg>',
    orderedList: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 6h11M10 12h11M10 18h11"/><path d="M4 6h1v4M4 10h2M4 18h1.5M4 14h2v4"/></svg>',
    quote: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 9h4v6H6l1-6zm8 0h4l-1 6h-3V9z"/></svg>',
    codeBlock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m8 9-3 3 3 3M16 9l3 3-3 3M13 6l-2 12"/></svg>',
    table: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="1"/><path d="M3 10h18M9 5v14M15 5v14"/></svg>',
    image: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="1.5"/><path d="m21 15-5-5L5 19"/></svg>',
    link: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 14a5 5 0 0 1 0-7l1-1a5 5 0 0 1 7 7l-1 1M14 10a5 5 0 0 1 0 7l-1 1a5 5 0 0 1-7-7l1-1"/></svg>',
    undo: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 7H5v4M5 11a7 7 0 1 0 1.4 4.2"/></svg>',
    redo: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 7h4v4M19 11a7 7 0 1 1-1.4 4.2"/></svg>',
};

function readInitialContent() {
    const node = document.getElementById('submission-editor-initial');

    if (! node) {
        return document.getElementById('content')?.value ?? '';
    }

    try {
        return JSON.parse(node.textContent ?? '""') ?? '';
    } catch {
        return '';
    }
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function createGroup() {
    const group = document.createElement('div');
    group.className = 'tnf-submission-editor__group';

    return group;
}

function iconButton(icon, title, action, command = null) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'tnf-submission-editor__btn';
    btn.innerHTML = icon;
    btn.title = title;
    btn.setAttribute('aria-label', title);

    if (command) {
        btn.dataset.command = command;
    }

    btn.addEventListener('click', action);

    return btn;
}

async function uploadInlineImage(file, uploadUrl) {
    const body = new FormData();
    body.append('image', file);

    const response = await fetch(uploadUrl, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body,
        credentials: 'same-origin',
    });

    if (! response.ok) {
        const payload = await response.json().catch(() => ({}));
        throw new Error(payload.message ?? 'Image upload failed.');
    }

    const payload = await response.json();

    if (! payload.url) {
        throw new Error('Image upload failed.');
    }

    return payload.url;
}

function mountSubmissionEditor() {
    const root = document.getElementById('submission-editor-root');

    if (! root) {
        return;
    }

    const hiddenInput = document.getElementById('content');
    const toolbar = document.getElementById('submission-editor-toolbar');
    const body = document.getElementById('submission-editor-body');
    const form = root.closest('form');
    const uploadUrl = root.dataset.uploadUrl ?? '';

    if (! hiddenInput || ! toolbar || ! body || ! form) {
        return;
    }

    const editor = new Editor({
        element: body,
        extensions: [
            StarterKit.configure({
                heading: { levels: [2, 3] },
            }),
            Underline,
            Subscript,
            Superscript,
            Link.configure({
                openOnClick: false,
                HTMLAttributes: { rel: 'noopener noreferrer' },
            }),
            TextAlign.configure({
                types: ['heading', 'paragraph'],
            }),
            Image.configure({
                inline: false,
                allowBase64: false,
            }),
            Table.configure({
                resizable: false,
            }),
            TableRow,
            TableHeader,
            TableCell,
            GapCursor,
            Placeholder.configure({
                placeholder: 'Write your news story here…',
            }),
        ],
        content: readInitialContent(),
        editorProps: {
            attributes: {
                class: 'tnf-submission-editor__content prose-tnf',
            },
        },
        onUpdate({ editor: current }) {
            hiddenInput.value = current.getHTML();
        },
    });

    hiddenInput.value = editor.getHTML();

    const syncActive = () => {
        toolbar.querySelectorAll('.tnf-submission-editor__btn[data-command]').forEach((el) => {
            const command = el.dataset.command;

            const active = {
                bold: editor.isActive('bold'),
                italic: editor.isActive('italic'),
                underline: editor.isActive('underline'),
                strike: editor.isActive('strike'),
                subscript: editor.isActive('subscript'),
                superscript: editor.isActive('superscript'),
                h2: editor.isActive('heading', { level: 2 }),
                h3: editor.isActive('heading', { level: 3 }),
                blockquote: editor.isActive('blockquote'),
                codeBlock: editor.isActive('codeBlock'),
            }[command] ?? false;

            el.classList.toggle('is-active', active);
        });
    };

    const run = (callback) => () => {
        callback();
        syncActive();
        hiddenInput.value = editor.getHTML();
    };

    const addToGroup = (group, icon, title, callback, command = null) => {
        group.appendChild(iconButton(icon, title, run(callback), command));
    };

    const groupText = createGroup();
    addToGroup(groupText, ICONS.bold, 'Bold', () => editor.chain().focus().toggleBold().run(), 'bold');
    addToGroup(groupText, ICONS.italic, 'Italic', () => editor.chain().focus().toggleItalic().run(), 'italic');
    addToGroup(groupText, ICONS.underline, 'Underline', () => editor.chain().focus().toggleUnderline().run(), 'underline');
    addToGroup(groupText, ICONS.strike, 'Strikethrough', () => editor.chain().focus().toggleStrike().run(), 'strike');
    addToGroup(groupText, ICONS.subscript, 'Subscript', () => editor.chain().focus().toggleSubscript().run(), 'subscript');
    addToGroup(groupText, ICONS.superscript, 'Superscript', () => editor.chain().focus().toggleSuperscript().run(), 'superscript');
    addToGroup(groupText, ICONS.clearFormat, 'Clear formatting', () => editor.chain().focus().clearNodes().unsetAllMarks().run());
    addToGroup(groupText, ICONS.link, 'Add link', () => {
        const previous = editor.getAttributes('link').href ?? '';
        const url = window.prompt('Paste link URL', previous);

        if (url === null) {
            return;
        }

        if (url === '') {
            editor.chain().focus().extendMarkRange('link').unsetLink().run();
        } else {
            editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
        }
    });
    toolbar.appendChild(groupText);

    const groupHeadings = createGroup();
    addToGroup(groupHeadings, ICONS.h2, 'Heading 2', () => editor.chain().focus().toggleHeading({ level: 2 }).run(), 'h2');
    addToGroup(groupHeadings, ICONS.h3, 'Heading 3', () => editor.chain().focus().toggleHeading({ level: 3 }).run(), 'h3');
    addToGroup(groupHeadings, ICONS.alignLeft, 'Align left', () => editor.chain().focus().setTextAlign('left').run());
    addToGroup(groupHeadings, ICONS.alignCenter, 'Align center', () => editor.chain().focus().setTextAlign('center').run());
    addToGroup(groupHeadings, ICONS.alignRight, 'Align right', () => editor.chain().focus().setTextAlign('right').run());
    toolbar.appendChild(groupHeadings);

    const groupLists = createGroup();
    addToGroup(groupLists, ICONS.quote, 'Quote', () => editor.chain().focus().toggleBlockquote().run(), 'blockquote');
    addToGroup(groupLists, ICONS.codeBlock, 'Code block', () => editor.chain().focus().toggleCodeBlock().run(), 'codeBlock');
    addToGroup(groupLists, ICONS.bulletList, 'Bullet list', () => editor.chain().focus().toggleBulletList().run());
    addToGroup(groupLists, ICONS.orderedList, 'Numbered list', () => editor.chain().focus().toggleOrderedList().run());
    toolbar.appendChild(groupLists);

    const groupInsert = createGroup();
    addToGroup(groupInsert, ICONS.table, 'Insert table', () => {
        editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run();
    });

    const imageInput = document.createElement('input');
    imageInput.type = 'file';
    imageInput.accept = 'image/jpeg,image/png,image/webp,image/gif';
    imageInput.className = 'sr-only';

    const imageButton = iconButton(ICONS.image, 'Insert image', () => {
        if (! uploadUrl) {
            window.alert('Image upload is not available right now.');
            return;
        }

        imageInput.click();
    });

    imageInput.addEventListener('change', async () => {
        const file = imageInput.files?.[0];
        imageInput.value = '';

        if (! file) {
            return;
        }

        imageButton.disabled = true;

        try {
            const url = await uploadInlineImage(file, uploadUrl);
            editor.chain().focus().setImage({ src: url }).run();
            hiddenInput.value = editor.getHTML();
        } catch (error) {
            window.alert(error instanceof Error ? error.message : 'Image upload failed.');
        } finally {
            imageButton.disabled = false;
        }
    });

    groupInsert.appendChild(imageButton);
    root.appendChild(imageInput);
    toolbar.appendChild(groupInsert);

    const groupHistory = createGroup();
    addToGroup(groupHistory, ICONS.undo, 'Undo', () => editor.chain().focus().undo().run());
    addToGroup(groupHistory, ICONS.redo, 'Redo', () => editor.chain().focus().redo().run());
    toolbar.appendChild(groupHistory);

    editor.on('selectionUpdate', syncActive);
    editor.on('transaction', syncActive);
    syncActive();

    form.addEventListener('submit', (event) => {
        hiddenInput.value = editor.getHTML();

        if (editor.getText().trim().length < 50) {
            event.preventDefault();
            window.alert('Please write at least 50 characters in your story.');
        }
    });
}

function mountFilePicker() {
    const input = document.getElementById('image');
    const label = document.getElementById('image-filename');

    if (! input || ! label) {
        return;
    }

    input.addEventListener('change', () => {
        const file = input.files?.[0];
        label.textContent = file ? file.name : 'No file chosen';
    });
}

document.addEventListener('DOMContentLoaded', () => {
    mountSubmissionEditor();
    mountFilePicker();
});
