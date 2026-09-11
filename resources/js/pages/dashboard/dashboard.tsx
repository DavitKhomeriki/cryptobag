import { Head, router, usePage } from "@inertiajs/react";
import Header from "@/pages/dashboard/components/Header";
import Balance from "@/pages/dashboard/components/Balance";
import SendContainer from "@/pages/dashboard/components/SendContainer";
import ReceiveContainer from "@/pages/dashboard/components/ReceiveContainer";
import TransactionContainer from "./components/TransactionContainer";
import { useEffect } from "react";
import echo from "@/echo";

export default function Dashboard() {
    const {
        auth,
        bitcoinUsd,
        walletBalance,
        addresses,
        transactions,
        transactionsNextCursor,
    } = usePage<{
        auth: any;
        bitcoinUsd: number;
        walletBalance: number;
        addresses: any;
        transactions: any;
        transactionsNextCursor: string | null;
    }>().props;

    useEffect(() => {
        if (!echo) {
            return;
        }

        const channelName = `wallet.${auth.user.id}`;
        const channel = echo.private(channelName);

        const refreshWallet = () => {
            router.reload({
                only: [
                    "walletBalance",
                    "transactions",
                    "transactionsNextCursor",
                ],
            });
        };

        channel.listen(".wallet.updated", refreshWallet);

        return () => {
            echo?.leave(channelName);
        };
    }, [auth.user.id]);

    return (
        <>
            <Head title="Dashboard" />
            <div className="bg-background text-foreground flex min-h-svh flex-col pt-10">
                <Header auth={auth} />

                <main className="mx-auto mt-12 w-full px-10">
                    <Balance
                        bitcoinUsd={bitcoinUsd}
                        walletBalance={walletBalance}
                    />

                    <div className="mt-10 grid w-full grid-cols-2 gap-7">
                        <SendContainer
                            walletBalance={walletBalance}
                            addresses={addresses.addresses}
                        />
                        <ReceiveContainer addresses={addresses.addresses} />
                        <TransactionContainer
                            transactions={transactions}
                            transactionsNextCursor={transactionsNextCursor}
                        />
                    </div>
                </main>
            </div>
        </>
    );
}
