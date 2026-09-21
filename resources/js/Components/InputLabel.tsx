import { LabelHTMLAttributes } from 'react';

/**
 * @deprecated Use standard <label> or incorporate into Neu components.
 */
export default function InputLabel({
    value,
    className = '',
    children,
    ...props
}: LabelHTMLAttributes<HTMLLabelElement> & { value?: string }) {
    return (
        <label
            {...props}
            className={`block font-medium text-sm text-neu-text ` + className}
        >
            {value ? value : children}
        </label>
    );
}
