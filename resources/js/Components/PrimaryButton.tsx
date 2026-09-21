import { ButtonHTMLAttributes } from 'react';
import { NeuButton } from '@/Components/ui/NeuButton';

/**
 * @deprecated Use NeuButton with variant="primary" instead.
 */
export default function PrimaryButton({
    className = '',
    disabled,
    children,
    ...props
}: ButtonHTMLAttributes<HTMLButtonElement>) {
    return (
        <NeuButton variant="primary" className={className} disabled={disabled} {...props}>
            {children}
        </NeuButton>
    );
}
