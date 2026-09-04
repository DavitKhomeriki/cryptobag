import { Head, Link, usePage } from "@inertiajs/react";
import { register } from "@/routes";

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Welcome" />
            <div className="bg-background text-foreground flex min-h-screen flex-col items-center p-6 lg:justify-center lg:p-8"></div>
        </>
    );
}
