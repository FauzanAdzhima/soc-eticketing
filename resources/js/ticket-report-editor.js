import { Editor, Extension } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import TextStyle from '@tiptap/extension-text-style';
import Color from '@tiptap/extension-color';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';

const FontSize = Extension.create({
    name: 'fontSize',

    addGlobalAttributes() {
        return [
            {
                types: ['textStyle'],
                attributes: {
                    fontSize: {
                        default: null,
                        parseHTML: (element) => element.style.fontSize || null,
                        renderHTML: (attributes) => {
                            if (!attributes.fontSize) {
                                return {};
                            }
                            return { style: `font-size: ${attributes.fontSize}` };
                        },
                    },
                },
            },
        ];
    },

    addCommands() {
        return {
            setFontSize:
                (fontSize) =>
                ({ chain }) =>
                    chain().setMark('textStyle', { fontSize }).run(),
            unsetFontSize:
                () =>
                ({ chain }) =>
                    chain().setMark('textStyle', { fontSize: null }).removeEmptyTextStyle().run(),
        };
    },
});

const mountedEditors = new WeakMap();

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

async function uploadImage(file, uploadUrl) {
    const payload = new FormData();
    payload.append('image', file);

    const response = await fetch(uploadUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
            Accept: 'application/json',
        },
        body: payload,
    });

    if (!response.ok) {
        throw new Error('Upload gambar gagal.');
    }

    const json = await response.json();
    if (!json?.url) {
        throw new Error('Respons upload tidak valid.');
    }

    return json.url;
}

function createIcon(name) {
    const icons = {
        paragraph: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6h7a4 4 0 0 1 0 8h-1v4h-2V8H8v10H6V6z"/></svg>',
        h1: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h2v6h6V5h2v14h-2v-6H6v6H4V5zm14 2h-2V5h4v14h-2V7z"/></svg>',
        h2: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h2v6h6V5h2v14h-2v-6H6v6H4V5zm12 4a3 3 0 0 1 3-3h1a3 3 0 0 1 0 6h-2a1 1 0 0 0-1 1v1h4v2h-6v-3a3 3 0 0 1 3-3h2a1 1 0 1 0 0-2h-1a1 1 0 0 0-1 1v1h-2V9z"/></svg>',
        bold: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 5h6a4 4 0 0 1 0 8H7V5zm0 10h7a4 4 0 0 1 0 8H7v-8zm2-8v4h4a2 2 0 1 0 0-4H9zm0 10v4h5a2 2 0 1 0 0-4H9z" transform="translate(0 -3)"/></svg>',
        italic: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 5h8v2h-3l-3 10h3v2H7v-2h3l3-10h-3V5z"/></svg>',
        strike: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 11h16v2H4v-2zm9.5-6a4.5 4.5 0 0 1 4.5 4.5h-2A2.5 2.5 0 0 0 13.5 7h-3A2.5 2.5 0 0 0 8 9.5c0 1.2.86 2.1 2.58 2.7l2.07.73C15.28 13.84 16 15.2 16 17a4 4 0 0 1-4 4h-1a4 4 0 0 1-4-4h2a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2c0-1.06-.56-1.74-2.02-2.25l-2.14-.76C7.26 13.1 6 11.57 6 9.5A4.5 4.5 0 0 1 10.5 5h3z"/></svg>',
        code: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18-6-6 6-6 1.4 1.4L5.8 12l4.6 4.6L9 18zm6 0-1.4-1.4 4.6-4.6-4.6-4.6L15 6l6 6-6 6z"/></svg>',
        bulletList: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h2v2H4V7zm0 4h2v2H4v-2zm0 4h2v2H4v-2zM8 7h12v2H8V7zm0 4h12v2H8v-2zm0 4h12v2H8v-2z"/></svg>',
        orderedList: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h2V5H4v2zm0 4h2v2H4v-2zm0 4h2v2H4v-2zM8 7h12v2H8V7zm0 4h12v2H8v-2zm0 4h12v2H8v-2z"/></svg>',
        blockquote: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 7h5v5H8v3h4v2H6v-6l1-4zm8 0h5v5h-4v3h4v2h-6v-6l1-4z"/></svg>',
        codeBlock: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 5h18v14H3V5zm2 2v10h14V7H5zm2 2h4v2H7V9zm0 4h10v2H7v-2z"/></svg>',
        hr: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 11h16v2H4v-2z"/></svg>',
        link: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.6 13.4 9.2 12l3.6-3.6a3 3 0 1 1 4.2 4.2l-1.8 1.8-1.4-1.4 1.8-1.8a1 1 0 1 0-1.4-1.4l-3.6 3.6zm2.8-2.8 1.4 1.4-3.6 3.6a3 3 0 1 1-4.2-4.2l1.8-1.8 1.4 1.4-1.8 1.8a1 1 0 1 0 1.4 1.4l3.6-3.6z"/></svg>',
        image: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1zm1 2v10h14V7H5zm3 2a2 2 0 1 1 0 4 2 2 0 0 1 0-4zm-2 7 3-3 2 2 3-3 4 4H6z"/></svg>',
        undo: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7.8 7H20v2H7.8l3.6 3.6L10 14 4 8l6-6 1.4 1.4L7.8 7z"/></svg>',
        redo: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16.2 7H4v2h12.2l-3.6 3.6L14 14l6-6-6-6-1.4 1.4L16.2 7z"/></svg>',
    };

    return icons[name] ?? '';
}

function createToolbarButton({ icon, title, onClick, isActive, canRun }) {
    const button = document.createElement('button');
    button.type = 'button';
    button.innerHTML = createIcon(icon);
    button.title = title;
    button.setAttribute('aria-label', title);
    button.className = 'ticket-editor-btn';
    button.addEventListener('click', onClick);

    return {
        button,
        refresh: () => {
            button.classList.toggle('is-active', Boolean(isActive?.()));
            button.disabled = !canRun?.();
        },
    };
}

function createToolbarSeparator() {
    const separator = document.createElement('span');
    separator.className = 'ticket-editor-separator';
    separator.setAttribute('aria-hidden', 'true');

    return separator;
}

function normalizeFontSize(rawValue) {
    const value = String(rawValue ?? '').trim().toLowerCase();
    if (value === '') {
        return '';
    }

    if (/^\d+(\.\d+)?$/.test(value)) {
        return `${value}px`;
    }

    if (/^\d+(\.\d+)?(px|rem|em|%)$/.test(value)) {
        return value;
    }

    return null;
}

function getCursorFontSize(editor) {
    const explicitFontSize = editor.getAttributes('textStyle').fontSize;
    if (explicitFontSize) {
        return explicitFontSize;
    }

    const pos = editor.state.selection.from;
    const domAtPos = editor.view.domAtPos(pos);
    let node = domAtPos.node;

    if (node?.nodeType === Node.TEXT_NODE) {
        node = node.parentElement;
    }

    if (node instanceof HTMLElement) {
        const computed = window.getComputedStyle(node).fontSize;
        return computed || '';
    }

    return '';
}

function mountSingleEditor(root) {
    if (mountedEditors.has(root)) {
        return;
    }

    const initialHtml = root.dataset.initialHtml ?? '';
    const uploadUrl = root.dataset.uploadUrl ?? '';
    const bodyField = root.querySelector('[data-editor-body]');
    const toolbar = root.querySelector('[data-editor-toolbar]');
    const contentEl = root.querySelector('[data-editor-content]');
    const nodeTypeSelect = root.querySelector('[data-editor-node-type]');
    const sizeSelect = root.querySelector('[data-editor-font-size]');
    const evidenceItems = root.querySelectorAll('[data-evidence-url]');

    if (!bodyField || !toolbar || !contentEl) {
        return;
    }

    const editor = new Editor({
        element: contentEl,
        extensions: [
            StarterKit,
            TextStyle,
            Color,
            FontSize,
            Link.configure({
                openOnClick: false,
                autolink: true,
                protocols: ['http', 'https'],
            }),
            Image.configure({
                allowBase64: false,
            }),
        ],
        content: initialHtml,
        onUpdate: ({ editor: activeEditor }) => {
            const html = activeEditor.getHTML();
            bodyField.value = html;
            root.dispatchEvent(new CustomEvent('ticket-report-editor:change', {
                bubbles: true,
                detail: { html },
            }));
        },
    });

    const controls = [
        createToolbarButton({
            icon: 'bold',
            title: 'Bold',
            onClick: () => editor.chain().focus().toggleBold().run(),
            isActive: () => editor.isActive('bold'),
            canRun: () => editor.can().chain().focus().toggleBold().run(),
        }),
        createToolbarButton({
            icon: 'italic',
            title: 'Italic',
            onClick: () => editor.chain().focus().toggleItalic().run(),
            isActive: () => editor.isActive('italic'),
            canRun: () => editor.can().chain().focus().toggleItalic().run(),
        }),
        createToolbarButton({
            icon: 'strike',
            title: 'Strike',
            onClick: () => editor.chain().focus().toggleStrike().run(),
            isActive: () => editor.isActive('strike'),
            canRun: () => editor.can().chain().focus().toggleStrike().run(),
        }),
        createToolbarButton({
            icon: 'code',
            title: 'Inline code',
            onClick: () => editor.chain().focus().toggleCode().run(),
            isActive: () => editor.isActive('code'),
            canRun: () => editor.can().chain().focus().toggleCode().run(),
        }),
        createToolbarButton({
            icon: 'bulletList',
            title: 'Bullet list',
            onClick: () => editor.chain().focus().toggleBulletList().run(),
            isActive: () => editor.isActive('bulletList'),
            canRun: () => editor.can().chain().focus().toggleBulletList().run(),
        }),
        createToolbarButton({
            icon: 'orderedList',
            title: 'Ordered list',
            onClick: () => editor.chain().focus().toggleOrderedList().run(),
            isActive: () => editor.isActive('orderedList'),
            canRun: () => editor.can().chain().focus().toggleOrderedList().run(),
        }),
        createToolbarButton({
            icon: 'blockquote',
            title: 'Blockquote',
            onClick: () => editor.chain().focus().toggleBlockquote().run(),
            isActive: () => editor.isActive('blockquote'),
            canRun: () => editor.can().chain().focus().toggleBlockquote().run(),
        }),
        createToolbarButton({
            icon: 'codeBlock',
            title: 'Code block',
            onClick: () => editor.chain().focus().toggleCodeBlock().run(),
            isActive: () => editor.isActive('codeBlock'),
            canRun: () => editor.can().chain().focus().toggleCodeBlock().run(),
        }),
        createToolbarButton({
            icon: 'hr',
            title: 'Horizontal rule',
            onClick: () => editor.chain().focus().setHorizontalRule().run(),
            canRun: () => editor.can().chain().focus().setHorizontalRule().run(),
        }),
        createToolbarButton({
            icon: 'link',
            title: 'Link',
            onClick: () => {
                const current = editor.getAttributes('link').href ?? '';
                const url = window.prompt('Masukkan URL:', current);
                if (url === null) return;
                if (url.trim() === '') {
                    editor.chain().focus().unsetLink().run();
                    return;
                }
                editor.chain().focus().setLink({ href: url.trim(), target: '_blank' }).run();
            },
            isActive: () => editor.isActive('link'),
            canRun: () => editor.can().chain().focus().setLink({ href: 'https://example.com' }).run(),
        }),
        createToolbarButton({
            icon: 'image',
            title: 'Upload image',
            onClick: async () => {
                if (!uploadUrl) return;

                const picker = document.createElement('input');
                picker.type = 'file';
                picker.accept = 'image/png,image/jpeg,image/webp,image/gif';
                picker.onchange = async () => {
                    const file = picker.files?.[0];
                    if (!file) return;
                    try {
                        const url = await uploadImage(file, uploadUrl);
                        editor.chain().focus().setImage({ src: url, alt: file.name }).run();
                    } catch (error) {
                        const message = error instanceof Error ? error.message : 'Upload gambar gagal.';
                        window.dispatchEvent(new CustomEvent('ticket-report-editor:error', { detail: { message } }));
                    }
                };
                picker.click();
            },
            canRun: () => true,
        }),
        createToolbarButton({
            icon: 'undo',
            title: 'Undo',
            onClick: () => editor.chain().focus().undo().run(),
            canRun: () => editor.can().chain().focus().undo().run(),
        }),
        createToolbarButton({
            icon: 'redo',
            title: 'Redo',
            onClick: () => editor.chain().focus().redo().run(),
            canRun: () => editor.can().chain().focus().redo().run(),
        }),
    ];

    const groups = [
        controls.slice(0, 4),
        controls.slice(4, 9),
        controls.slice(9, 11),
        controls.slice(11),
    ];

    groups.forEach((group, index) => {
        group.forEach(({ button }) => toolbar.appendChild(button));
        if (index < groups.length - 1) {
            toolbar.appendChild(createToolbarSeparator());
        }
    });

    if (sizeSelect) {
        const applyFontSizeFromInput = () => {
            const normalized = normalizeFontSize(sizeSelect.value);
            if (normalized === null) {
                return;
            }
            if (normalized === '') {
                editor.chain().focus().unsetFontSize().run();
                sizeSelect.value = '';
                return;
            }
            editor.chain().focus().setFontSize(normalized).run();
            sizeSelect.value = normalized;
        };

        sizeSelect.addEventListener('change', applyFontSizeFromInput);
    }

    if (nodeTypeSelect) {
        nodeTypeSelect.addEventListener('change', () => {
            const value = nodeTypeSelect.value;
            if (value === 'paragraph') {
                editor.chain().focus().setParagraph().run();
                return;
            }
            if (value === 'heading-1') {
                editor.chain().focus().setHeading({ level: 1 }).run();
                return;
            }
            if (value === 'heading-2') {
                editor.chain().focus().setHeading({ level: 2 }).run();
                return;
            }
            if (value === 'heading-3') {
                editor.chain().focus().setHeading({ level: 3 }).run();
            }
        });
    }

    const refreshControls = () => {
        controls.forEach((control) => control.refresh());
        if (nodeTypeSelect) {
            if (editor.isActive('heading', { level: 1 })) {
                nodeTypeSelect.value = 'heading-1';
            } else if (editor.isActive('heading', { level: 2 })) {
                nodeTypeSelect.value = 'heading-2';
            } else if (editor.isActive('heading', { level: 3 })) {
                nodeTypeSelect.value = 'heading-3';
            } else {
                nodeTypeSelect.value = 'paragraph';
            }
        }
        if (sizeSelect) {
            const currentSize = getCursorFontSize(editor);
            if ([...sizeSelect.options].some((opt) => opt.value === currentSize)) {
                sizeSelect.value = currentSize;
            } else {
                sizeSelect.value = '';
            }
        }
    };
    editor.on('selectionUpdate', refreshControls);
    editor.on('transaction', refreshControls);
    refreshControls();

    const insertImageAtSelection = (url) => {
        if (!url) {
            return;
        }
        editor.chain().focus().setImage({ src: url }).run();
    };

    root.addEventListener('ticket-report-editor:replace-content', (event) => {
        const html = event?.detail?.html ?? '';
        editor.commands.setContent(html, true);
    });

    evidenceItems.forEach((item) => {
        const url = item.getAttribute('data-evidence-url') ?? '';
        item.addEventListener('click', () => insertImageAtSelection(url));
        item.addEventListener('dragstart', (event) => {
            if (!event.dataTransfer) {
                return;
            }
            event.dataTransfer.setData('text/plain', url);
            event.dataTransfer.setData('application/x-ticket-evidence-url', url);
            event.dataTransfer.effectAllowed = 'copy';
        });
    });

    contentEl.addEventListener('dragover', (event) => {
        const transfer = event.dataTransfer;
        if (!transfer) {
            return;
        }
        const hasUrl = Array.from(transfer.types).includes('application/x-ticket-evidence-url')
            || Array.from(transfer.types).includes('text/plain');
        if (hasUrl) {
            event.preventDefault();
            transfer.dropEffect = 'copy';
        }
    });

    contentEl.addEventListener('drop', (event) => {
        const transfer = event.dataTransfer;
        if (!transfer) {
            return;
        }
        const droppedUrl = transfer.getData('application/x-ticket-evidence-url') || transfer.getData('text/plain');
        if (!droppedUrl) {
            return;
        }
        event.preventDefault();
        const coords = editor.view.posAtCoords({ left: event.clientX, top: event.clientY });
        if (coords?.pos !== undefined) {
            editor.chain().focus().insertContentAt(coords.pos, {
                type: 'image',
                attrs: { src: droppedUrl },
            }).run();
        } else {
            insertImageAtSelection(droppedUrl);
        }
    });

    bodyField.value = editor.getHTML();
    mountedEditors.set(root, editor);
}

function mountAllEditors() {
    document.querySelectorAll('[data-ticket-report-editor]').forEach((root) => mountSingleEditor(root));
}

document.addEventListener('DOMContentLoaded', mountAllEditors);
document.addEventListener('livewire:navigated', mountAllEditors);
