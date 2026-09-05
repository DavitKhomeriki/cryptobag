type Props = {
    wallets: unknown;
};

export default function Index({ wallets }: Props) {
    return <pre className="p-6">{JSON.stringify(wallets, null, 2)}</pre>;
}
