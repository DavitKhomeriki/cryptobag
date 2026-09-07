type BalanceProps = {
    bitcoinUsd: number;
    walletBalance: number;
};

export default function Balance({ bitcoinUsd, walletBalance }: BalanceProps) {
    return (
        <div className="flex flex-col">
            <p> 1 tbtc = ${bitcoinUsd.toFixed(2)} USD</p>
            <p>
                {' '}
                Balance: {walletBalance.toFixed(8)} tbtc4,{' '}
                {(walletBalance * bitcoinUsd).toFixed(2)} USD, Max spendable
                amount: {walletBalance.toFixed(8)} tbtc4{' '}
            </p>
        </div>
    );
}
