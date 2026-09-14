import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

type Props = {
    passwordRules: string;
};

export default function Register({ passwordRules }: Props) {
    const [accountType, setAccountType] = useState<'user' | 'merchant'>('user');

    return (
        <>
            <Head title="Register" />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <input
                                type="hidden"
                                name="account_type"
                                value={accountType}
                            />

                            <div className="grid gap-2">
                                <Label>Account type</Label>
                                <div className="grid grid-cols-2 gap-2">
                                    <Button
                                        type="button"
                                        variant={
                                            accountType === 'user'
                                                ? 'default'
                                                : 'outline'
                                        }
                                        onClick={() => setAccountType('user')}
                                        tabIndex={1}
                                    >
                                        User
                                    </Button>
                                    <Button
                                        type="button"
                                        variant={
                                            accountType === 'merchant'
                                                ? 'default'
                                                : 'outline'
                                        }
                                        onClick={() =>
                                            setAccountType('merchant')
                                        }
                                        tabIndex={2}
                                    >
                                        Merchant
                                    </Button>
                                </div>
                                <InputError message={errors.account_type} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="username">Username</Label>
                                <Input
                                    id="username"
                                    type="text"
                                    required
                                    autoFocus
                                    tabIndex={3}
                                    autoComplete="username"
                                    name="username"
                                    placeholder="Username"
                                />
                                <InputError
                                    message={errors.username}
                                    className="mt-2"
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    tabIndex={4}
                                    autoComplete="email"
                                    name="email"
                                    placeholder="email@example.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            {accountType === 'merchant' && (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="merchant_name">
                                            Merchant name
                                        </Label>
                                        <Input
                                            id="merchant_name"
                                            type="text"
                                            required
                                            tabIndex={5}
                                            autoComplete="organization"
                                            name="merchant_name"
                                            placeholder="Merchant name"
                                        />
                                        <InputError
                                            message={errors.merchant_name}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="merchant_callback_url">
                                            Callback URL
                                        </Label>
                                        <Input
                                            id="merchant_callback_url"
                                            type="url"
                                            tabIndex={6}
                                            name="merchant_callback_url"
                                            placeholder="https://example.com/webhooks/cryptobag"
                                        />
                                        <InputError
                                            message={
                                                errors.merchant_callback_url
                                            }
                                        />
                                    </div>
                                </>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="password">Password</Label>
                                <PasswordInput
                                    id="password"
                                    required
                                    tabIndex={7}
                                    autoComplete="new-password"
                                    name="password"
                                    placeholder="Password"
                                    passwordrules={passwordRules}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    Confirm password
                                </Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    required
                                    tabIndex={8}
                                    autoComplete="new-password"
                                    name="password_confirmation"
                                    placeholder="Confirm password"
                                    passwordrules={passwordRules}
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <Button
                                type="submit"
                                className="mt-2 w-full"
                                tabIndex={9}
                                data-test="register-user-button"
                            >
                                {processing && <Spinner />}
                                Create account
                            </Button>
                        </div>

                        <div className="text-muted-foreground text-center text-sm">
                            Already have an account?{' '}
                            <TextLink href={login()} tabIndex={10}>
                                Log in
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

Register.layout = {
    title: 'Create an account',
    description: 'Enter your details below to create your account',
};
