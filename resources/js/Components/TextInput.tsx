import { forwardRef, InputHTMLAttributes } from 'react';

import { NeuInput } from '@/Components/ui/NeuInput';

/**
 * @deprecated Use NeuInput instead.
 */
export default forwardRef<
    HTMLInputElement,
    InputHTMLAttributes<HTMLInputElement> & { isFocused?: boolean }
>(function TextInput(
    { type = 'text', className = '', isFocused = false, ...props },
    ref,
) {
    return (
        <NeuInput
            {...props}
            type={type}
            className={className}
            ref={ref}
            autoFocus={isFocused}
        />
    );
});
