import { EditorContent, useEditor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import {
    Bold,
    Italic,
    Underline as UnderlineIcon,
    Strikethrough,
    Heading2,
    Heading3,
    Type,
    SmilePlus,
} from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { Toggle } from '@/components/ui/toggle';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';

const EMOJI_LIST = [
    '😀', '😂', '😍', '🥰', '😎', '🤔', '😢', '😡',
    '👍', '👎', '❤️', '⭐', '🔥', '✅', '❌', '⚠️',
    '🎉', '🎂', '🛒', '🏠', '📞', '💡', '📝', '🗓️',
    '🍕', '🍎', '☕', '🚗', '✈️', '🌞', '🌧️', '❄️',
];

interface RichTextEditorProps {
    content: string;
    onChange: (html: string) => void;
    editable?: boolean;
    textColor?: string;
    placeholder?: string;
}

export default function RichTextEditor({
    content,
    onChange,
    editable = true,
    textColor,
    placeholder = 'Notiz schreiben...',
}: RichTextEditorProps) {
    const [emojiPickerOpen, setEmojiPickerOpen] = useState(false);
    const emojiRef = useRef<HTMLDivElement>(null);

    const editor = useEditor({
        extensions: [
            StarterKit.configure({
                heading: { levels: [2, 3] },
            }),
        ],
        content,
        editable,
        editorProps: {
            attributes: {
                class: cn(
                    'prose prose-sm md:prose-base max-w-none min-h-[200px] w-full border-none bg-transparent outline-none focus:outline-none',
                    'prose-headings:font-semibold prose-headings:tracking-tight',
                    'prose-p:my-1 prose-headings:my-2',
                    '[&_.is-editor-empty:first-child::before]:text-muted-foreground/50',
                    '[&_.is-editor-empty:first-child::before]:content-[attr(data-placeholder)]',
                    '[&_.is-editor-empty:first-child::before]:float-left',
                    '[&_.is-editor-empty:first-child::before]:h-0',
                    '[&_.is-editor-empty:first-child::before]:pointer-events-none',
                ),
            },
        },
        onUpdate: ({ editor: e }) => {
            onChange(e.getHTML());
        },
    });

    // Update editor content from outside
    useEffect(() => {
        if (editor && content !== editor.getHTML()) {
            editor.commands.setContent(content, false);
        }
    }, [content, editor]);

    // Update editable state
    useEffect(() => {
        if (editor) {
            editor.setEditable(editable);
        }
    }, [editable, editor]);

    // Close emoji picker on outside click
    useEffect(() => {
        function handleClickOutside(e: MouseEvent) {
            if (
                emojiRef.current &&
                !emojiRef.current.contains(e.target as Node)
            ) {
                setEmojiPickerOpen(false);
            }
        }
        if (emojiPickerOpen) {
            document.addEventListener('mousedown', handleClickOutside);
        }
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, [emojiPickerOpen]);

    const insertEmoji = useCallback(
        (emoji: string) => {
            editor?.chain().focus().insertContent(emoji).run();
            setEmojiPickerOpen(false);
        },
        [editor],
    );

    if (!editor) return null;

    return (
        <div className="flex flex-1 flex-col">
            {/* Toolbar */}
            {editable && (
                <div
                    className="flex flex-wrap items-center gap-0.5 border-b px-2 py-1.5"
                    style={textColor ? { color: textColor } : undefined}
                >
                    {/* Text size */}
                    <Toggle
                        size="sm"
                        pressed={editor.isActive('heading', { level: 2 })}
                        onPressedChange={() =>
                            editor.chain().focus().toggleHeading({ level: 2 }).run()
                        }
                        title="Groß"
                    >
                        <Heading2 className="size-4" />
                    </Toggle>
                    <Toggle
                        size="sm"
                        pressed={editor.isActive('heading', { level: 3 })}
                        onPressedChange={() =>
                            editor.chain().focus().toggleHeading({ level: 3 }).run()
                        }
                        title="Mittel"
                    >
                        <Heading3 className="size-4" />
                    </Toggle>
                    <Toggle
                        size="sm"
                        pressed={!editor.isActive('heading')}
                        onPressedChange={() =>
                            editor.chain().focus().setParagraph().run()
                        }
                        title="Klein"
                    >
                        <Type className="size-4" />
                    </Toggle>

                    <Separator orientation="vertical" className="mx-1 h-5" />

                    {/* Formatting */}
                    <Toggle
                        size="sm"
                        pressed={editor.isActive('bold')}
                        onPressedChange={() =>
                            editor.chain().focus().toggleBold().run()
                        }
                        title="Fett"
                    >
                        <Bold className="size-4" />
                    </Toggle>
                    <Toggle
                        size="sm"
                        pressed={editor.isActive('italic')}
                        onPressedChange={() =>
                            editor.chain().focus().toggleItalic().run()
                        }
                        title="Kursiv"
                    >
                        <Italic className="size-4" />
                    </Toggle>
                    <Toggle
                        size="sm"
                        pressed={editor.isActive('underline')}
                        onPressedChange={() =>
                            editor.chain().focus().toggleUnderline().run()
                        }
                        title="Unterstrichen"
                    >
                        <UnderlineIcon className="size-4" />
                    </Toggle>
                    <Toggle
                        size="sm"
                        pressed={editor.isActive('strike')}
                        onPressedChange={() =>
                            editor.chain().focus().toggleStrike().run()
                        }
                        title="Durchgestrichen"
                    >
                        <Strikethrough className="size-4" />
                    </Toggle>

                    <Separator orientation="vertical" className="mx-1 h-5" />

                    {/* Emoji picker */}
                    <div className="relative" ref={emojiRef}>
                        <Toggle
                            size="sm"
                            pressed={emojiPickerOpen}
                            onPressedChange={() => setEmojiPickerOpen(!emojiPickerOpen)}
                            title="Emojis"
                        >
                            <SmilePlus className="size-4" />
                        </Toggle>
                        {emojiPickerOpen && (
                            <div className="absolute top-full left-0 z-50 mt-1 grid w-64 grid-cols-8 gap-0.5 rounded-lg border bg-popover p-2 shadow-lg">
                                {EMOJI_LIST.map((emoji) => (
                                    <button
                                        key={emoji}
                                        type="button"
                                        className="flex size-8 items-center justify-center rounded text-lg transition-colors hover:bg-accent"
                                        onClick={() => insertEmoji(emoji)}
                                    >
                                        {emoji}
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            )}

            {/* Editor */}
            <div
                className="flex-1 p-4 md:p-6"
                style={textColor ? { color: textColor } : undefined}
            >
                <EditorContent editor={editor} />
            </div>
        </div>
    );
}
