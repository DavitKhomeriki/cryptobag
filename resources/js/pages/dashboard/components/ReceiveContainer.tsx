import { router } from "@inertiajs/react";
import { CopyPlus, HardDriveDownloadIcon, Loader2 } from "lucide-react";
import { useState } from "react";

type ReceiveContainerProps = {
    addresses: any[];
};

export default function ReceiveContainer({ addresses }: ReceiveContainerProps) {
    const [isLoading, setIsLoading] = useState(false);

    const addAddress = () => {
        router.post(
            "/dashboard/address",
            {},
            {
                onStart: () => {
                    setIsLoading(true);
                },

                onFinish: () => {
                    setIsLoading(false);
                },
            },
        );
    };

    return (
        <div className="flex flex-col items-start gap-2 border border-green-900 p-4">
            <h2 className="flex items-center gap-2 text-xl font-medium">
                <HardDriveDownloadIcon strokeWidth={1.5} />
                Receive
            </h2>

            <div className="mt-2 flex w-full flex-col gap-1 ">
                <h3>Your Address:</h3>
                <div className="flex flex-col max-h-35 overflow-y-auto gap-1 scrollbar-thin scrollbar-thumb-green-900 pr-1">
                    {addresses.map((address: any) => (
                        <p
                            className="w-full rounded-lg border p-2"
                            key={address.id}
                        >
                            {address.address}
                        </p>
                    ))}
                </div>
            </div>

            <button
                onClick={addAddress}
                disabled={isLoading}
                className="mt-4 flex cursor-pointer gap-2 rounded-sm border-2 px-4 py-2 text-sm font-medium transition duration-300 hover:bg-green-500 hover:text-black disabled:cursor-not-allowed disabled:opacity-50"
            >
                {isLoading ? (
                    <>
                        <Loader2
                            className="animate-spin"
                            strokeWidth={1.5}
                            size={17.9}
                        />
                        Creating...
                    </>
                ) : (
                    <>
                        <CopyPlus strokeWidth={1.5} size={17.9} />
                        Add new address
                    </>
                )}
            </button>
        </div>
    );
}
