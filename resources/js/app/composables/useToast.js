import { useNotificationsStore } from '../stores/notifications';

/**
 * Transient success/error toasts for write actions. Uses pushToast (not notify)
 * so routine CRUD confirmations don't pile up in the notification bell.
 */
export function useToast() {
    const notifications = useNotificationsStore();

    return {
        success: (message, title = null) => notifications.pushToast({ type: 'success', title, message }),
        error: (message, title = null) => notifications.pushToast({ type: 'error', title, message }),
        info: (message, title = null) => notifications.pushToast({ type: 'info', title, message }),
    };
}

/**
 * Pull a human error message out of an API error (validation message or fallback).
 */
export function errorMessage(error, fallback = 'Something went wrong.') {
    return error?.data?.message || fallback;
}
