import React from 'react';

export default function LoadingScreen() {
  const fireworkStyles = `
    @keyframes launch {
      0% { transform: translateY(100vh) scale(0.3); opacity: 1; }
      60% { transform: translateY(var(--top)) scale(0.8); opacity: 1; }
      65% { opacity: 0; }
      100% { transform: translateY(var(--top)); opacity: 0; }
    }

    @keyframes burst {
      0%, 60% { transform: scale(0); opacity: 0; }
      65% { transform: scale(0.1); opacity: 1; }
      85% { opacity: 1; }
      100% { transform: scale(1.2); opacity: 0; }
    }

    @keyframes spark {
      0%, 60% { transform: translate(0, 0) scale(1); opacity: 0; }
      65% { opacity: 1; }
      100% { transform: translate(var(--dx), var(--dy)) scale(0); opacity: 0; }
    }

    .firework-rocket {
      animation: launch 4s infinite ease-out;
    }

    .firework-burst {
      animation: burst 4s infinite ease-out;
    }

    .firework-spark {
      animation: spark 4s infinite ease-out;
    }
  `;

  // Define 4 fireworks with different properties
  const fireworks = [
    { id: 1, left: '15%', top: '25vh', delay: '0s', color: 'bg-rose-500 shadow-[0_0_8px_#f43f5e]' },
    { id: 2, left: '80%', top: '20vh', delay: '1s', color: 'bg-amber-400 shadow-[0_0_8px_#fbbf24]' },
    { id: 3, left: '30%', top: '40vh', delay: '2s', color: 'bg-emerald-400 shadow-[0_0_8px_#34d399]' },
    { id: 4, left: '65%', top: '35vh', delay: '3s', color: 'bg-indigo-400 shadow-[0_0_8px_#818cf8]' }
  ];

  return (
    <div className="min-h-screen flex flex-col items-center justify-center bg-slate-950 text-white relative overflow-hidden select-none">
      <style dangerouslySetInnerHTML={{ __html: fireworkStyles }} />

      {/* Fireworks animation container */}
      <div className="absolute inset-0 pointer-events-none z-0">
        {fireworks.map((fw) => (
          <React.Fragment key={fw.id}>
            {/* Rocket trail */}
            <div 
              className="absolute bottom-0 w-1 bg-gradient-to-t from-transparent to-amber-300 rounded-full firework-rocket flex items-center justify-center" 
              style={{ 
                animationDelay: fw.delay, 
                left: fw.left,
                '--top': fw.top,
                height: '40px'
              }}
            >
              <div className="w-1.5 h-1.5 bg-white rounded-full animate-ping" />
            </div>

            {/* Burst explosion */}
            <div 
              className="absolute firework-burst flex items-center justify-center" 
              style={{ 
                animationDelay: fw.delay, 
                left: fw.left, 
                top: fw.top 
              }}
            >
              {[...Array(16)].map((_, i) => {
                const angle = (i * 22.5 * Math.PI) / 180;
                const distance = 90; // explosion size
                const dx = `${Math.cos(angle) * distance}px`;
                const dy = `${Math.sin(angle) * distance}px`;
                return (
                  <div 
                    key={i} 
                    className={`absolute w-1.5 h-1.5 rounded-full firework-spark ${fw.color}`} 
                    style={{ 
                      '--dx': dx, 
                      '--dy': dy, 
                      animationDelay: fw.delay 
                    }}
                  />
                );
              })}
            </div>
          </React.Fragment>
        ))}
      </div>

      {/* Floating Sparkly Stars in foreground */}
      <div className="absolute inset-0 pointer-events-none z-10">
        <i className="fa-solid fa-star text-amber-300 absolute text-xs top-1/4 left-1/3 animate-ping" style={{ animationDuration: '3s' }} />
        <i className="fa-solid fa-star text-rose-300 absolute text-[10px] top-1/3 right-1/4 animate-pulse" style={{ animationDuration: '2s' }} />
        <i className="fa-solid fa-star text-emerald-300 absolute text-xs bottom-1/3 left-1/5 animate-pulse" style={{ animationDuration: '4s' }} />
        <i className="fa-solid fa-star text-blue-300 absolute text-[9px] top-[15%] right-1/3 animate-ping" style={{ animationDuration: '2.5s' }} />
      </div>
    </div>
  );
}
