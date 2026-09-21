import { HTMLAttributes } from 'react';

/**
 * @deprecated Use standard error text or incorporate into Neu components.
 */
export default function InputError({
    message,
    className = '',
    ...props
}: HTMLAttributes<HTMLParagraphElement> & { message?: string }) {
    return message ? (
        <p
            {...props}
            className={'text-sm text-status-failed-text ' + className}
        >
            {message}
        </p>
    ) : null;
}
