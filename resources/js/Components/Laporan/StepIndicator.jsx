/**
 * StepIndicator Component for Multi-Step Form
 * 
 * Updated steps for refactored Laporan structure:
 * - Step 0: Administrasi (STPA, Petugas)
 * - Step 1: Data Pelapor (Identitas, Kontak, Alamat)
 * - Step 2: Laporan Kejadian (Detail + Korban[])
 * - Step 3: Tersangka & Modus (Tersangka[] + Identitas[])
 */

const steps = [
    { id: 0, name: 'Administrasi', description: 'Nomor STPA & Petugas' },
    { id: 1, name: 'Data Pelapor', description: 'Identitas, Kontak & Alamat' },
    { id: 2, name: 'Laporan Kejadian', description: 'Detail & Data Korban' },
    { id: 3, name: 'Tersangka & Modus', description: 'Identitas Digital & Kronologi' },
];

export default function StepIndicator({ currentStep, onStepClick }) {
    return (
        <nav aria-label="Progress" className="mb-8">
            <ol className="flex items-center justify-between">
                {steps.map((step, stepIdx) => (
                    <li key={step.id} className="relative flex-1">
                        {/* Connector Line */}
                        {stepIdx !== steps.length - 1 && (
                            <div
                                className={`absolute left-1/2 top-4 w-full h-0.5 ${step.id < currentStep ? 'bg-tactical-accent' : 'bg-gray-200'
                                    }`}
                                aria-hidden="true"
                            />
                        )}

                        <button
                            type="button"
                            onClick={() => onStepClick?.(step.id)}
                            disabled={step.id > currentStep}
                            className="relative flex flex-col items-center group disabled:cursor-not-allowed"
                        >
                            {/* Step Circle */}
                            <span className={`
                                relative z-10 flex items-center justify-center w-8 h-8 rounded-full
                                text-sm font-semibold transition-all duration-300
                                ${currentStep === step.id
                                    ? 'bg-tactical-accent text-white ring-4 ring-tactical-accent/20'
                                    : step.id < currentStep
                                        ? 'bg-tactical-success text-white'
                                        : 'bg-white border-2 border-gray-300 text-gray-500 group-hover:border-tactical-accent group-disabled:hover:border-gray-300'
                                }
                            `}>
                                {step.id < currentStep ? (
                                    <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                                    </svg>
                                ) : (
                                    step.id + 1
                                )}
                            </span>

                            {/* Step Label */}
                            <span className={`
                                mt-2 text-xs font-semibold uppercase tracking-wide
                                ${currentStep === step.id ? 'text-tactical-accent' : 'text-gray-500'}
                            `}>
                                {step.name}
                            </span>

                            {/* Step Description - Hidden on mobile */}
                            <span className="hidden sm:block mt-0.5 text-xs text-gray-400">
                                {step.description}
                            </span>
                        </button>
                    </li>
                ))}
            </ol>
        </nav>
    );
}

// Export steps for use in parent components
export { steps };
