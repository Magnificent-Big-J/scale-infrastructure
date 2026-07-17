import DOMPurify from 'dompurify';

const ALLOWED_TAGS = ['p', 'br', 'strong', 'em', 'u', 's', 'ol', 'ul', 'li', 'blockquote', 'a', 'h1', 'h2', 'h3'];
const ALLOWED_ATTR = ['href', 'target', 'rel'];

export function sanitizeRichText(html) {
    if (!html) return '';

    return DOMPurify.sanitize(html, { ALLOWED_TAGS, ALLOWED_ATTR, ALLOWED_URI_REGEXP: /^(?:https?:)?\/\// });
}

export function isRichTextEmpty(html) {
    if (!html) return true;

    return DOMPurify.sanitize(html, { ALLOWED_TAGS: [] }).trim().length === 0;
}
