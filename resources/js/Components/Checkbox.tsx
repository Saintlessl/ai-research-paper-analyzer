import { InputHTMLAttributes } from 'react';
import { NeuCheckbox } from '@/Components/ui/NeuForm';

/**
 * @deprecated Use NeuCheckbox instead.
 */
export default function Checkbox({
    className = '',
    ...props
}: InputHTMLAttributes<HTMLInputElement>) {
    return (
        <NeuCheckbox
            {...props}
            className={className}
        />
    );
}
