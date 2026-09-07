import { Head, usePage } from "@inertiajs/react";
import Header from "@/pages/dashboard/components/Header";
import Balance from "@/pages/dashboard/components/Balance";
import SendContainer from "@/pages/dashboard/components/SendContainer";
import ReceiveContainer from "@/pages/dashboard/components/ReceiveContainer";

export default function Dashboard() {
    const { auth, bitcoinUsd, walletBalance, addresses } = usePage<{
        auth: any;
        bitcoinUsd: number;
        walletBalance: number;
        addresses: any;
    }>().props;

    console.log(auth, bitcoinUsd, walletBalance, addresses);

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex flex-col bg-background text-foreground min-h-svh pt-10">
                <Header auth={auth} />

                <main className="mx-auto mt-12 w-full px-10">
                    <Balance
                        bitcoinUsd={bitcoinUsd}
                        walletBalance={walletBalance}
                    />

                    <div className="mt-10 grid grid-cols-2 w-full gap-7">
                        <SendContainer walletBalance={walletBalance} />
                        <ReceiveContainer addresses={addresses.addresses} />
                    </div>
                </main>
            </div>
        </>
    );
}
