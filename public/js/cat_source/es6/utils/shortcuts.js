const Shortcuts = {
  shortCutsKeyType: navigator.platform === 'MacIntel' ? 'mac' : 'standard',
  cattol_formatting_characters: {
    label: 'Vormindamine',
    events: {
      nonBreakingSpace: {
        label: 'Püsitühik',
        equivalent: '',
        keystrokes: {
          standard: 'ctrl+shift+space',
          mac: 'option+space',
        },
      },
      wordJoiner: {
        label: 'Sõnade ühendaja',
        equivalent: '',
        keystrokes: {
          standard: 'ctrl+alt+space',
          mac: 'shift+space',
        },
      },
    },
  },
  cattol: {
    label: 'Funktsionaalsus',
    events: {
      openShortcutsModal: {
        label: 'Ava otseteede aken',
        equivalent: 'Open shortcuts window',
        keystrokes: {
          standard: 'alt+h',
          mac: 'ctrl+h',
        },
      },
      translate: {
        label: 'Kinnita tõlge',
        equivalent: 'click on Translated',
        keystrokes: {
          standard: 'ctrl+return',
          mac: 'meta+return',
        },
      },
      translate_nextUntranslated: {
        label: 'Kinnita tõlge ja liigu järgmise tõlkimata segmendi juurde',
        equivalent: 'click on [T+>>]',
        keystrokes: {
          standard: 'ctrl+shift+return',
          mac: 'meta+shift+return',
        },
      },
      openNext: {
        label: 'Liigu järgmisele segmendile',
        equivalent: '',
        keystrokes: {
          standard: 'ctrl+down',
          mac: 'meta+down',
        },
      },
      openPrevious: {
        label: 'Liigu eelmisele segmendile',
        equivalent: '',
        keystrokes: {
          standard: 'ctrl+up',
          mac: 'meta+up',
        },
      },
      gotoCurrent: {
        label: 'Liigu praegusele segmendile',
        equivalent: '',
        keystrokes: {
          standard: 'ctrl+shift+f',
          mac: 'meta+shift+f',
        },
      },
      copySource: {
        label: 'Kopeeri sihtkeel lähtekeelde',
        equivalent: 'click on > between source and target',
        keystrokes: {
          standard: 'ctrl+i',
          mac: 'ctrl+i',
        },
      },
      undoInSegment: {
        label: 'Muudatuse tagasivõtmine segmendis',
        equivalent: '',
        keystrokes: {
          standard: 'ctrl+z',
          mac: 'meta+z',
        },
      },
      redoInSegment: {
        label: 'Muudatuse uuesti tegemine segmendis',
        equivalent: '',
        keystrokes: {
          standard: 'ctrl+y',
          mac: 'meta+shift+z',
        },
      },
      openSearch: {
        label: 'Ava otsingupaneel',
        equivalent: '',
        keystrokes: {
          standard: 'ctrl+f',
          mac: 'meta+f',
        },
      },
      searchInConcordance: {
        label:
          'TM otsing lähtesegmendis valitud sõna(de)le',
        equivalent: '',
        keystrokes: {
          standard: 'alt+k',
          mac: 'meta+k',
        },
      },
      // openSettings: {
      //   label: 'Ava seadete paneel',
      //   equivalent: '',
      //   keystrokes: {
      //     standard: 'ctrl+shift+s',
      //     mac: 'meta+shift+s',
      //   },
      // },
      openComments: {
        label: 'Ava kommentaarid praeguses segmendis',
        equivalent: '',
        keystrokes: {
          standard: 'ctrl+shift+c',
          mac: 'meta+shift+c',
        },
      },
      openIssuesPanel: {
        label: 'Ava tähelepanekute paneel',
        equivalent: '',
        keystrokes: {
          standard: 'ctrl+shift+a',
          mac: 'meta+shift+a',
        },
      },
      navigateIssues: {
        label: 'Navigeeri tähelepanekute paneelile / lisa tähelepanek',
        equivalent: {
          standard: 'Ctrl + Alt + Arrows/Enter',
          mac: 'Ctrl + Option + Arrows/Enter',
        },
        keystrokes: {
          standard: 'ctrl+alt+arrows-enter',
          mac: 'ctrl+option+arrows-enter',
        },
      },
      copyContribution1: {
        label: 'Kopeeri esimene tõlkevaste sihtkeelde',
        equivalent: '',
        keystrokes: {
          standard: 'ctrl+1',
          mac: 'ctrl+1',
        },
      },
      copyContribution2: {
        label: 'Kopeeri teine tõlkevaste sihtkeelde',
        equivalent: '',
        keystrokes: {
          standard: 'ctrl+2',
          mac: 'ctrl+2',
        },
      },
      copyContribution3: {
        label: 'Kopeeri kolmas tõlkevaste sihtkeelde',
        equivalent: '',
        keystrokes: {
          standard: 'ctrl+3',
          mac: 'ctrl+3',
        },
      },
      splitSegment: {
        label: 'Poolita segment',
        equivalent: '',
        keystrokes: {
          standard: 'ctrl+s',
          mac: 'ctrl+s',
        },
      },
      addNextTag: {
        label: 'Ava vormingu siltide menüü',
        equivalent: '',
        keystrokes: {
          standard: 'alt+t',
          mac: 'option+t',
        },
      },
      navigateTabs: {
        label: 'Navigeeri segmendi vahekaartidel',
        equivalent: '',
        keystrokes: {
          standard: 'alt+s',
          mac: 'ctrl+option+s',
        },
      },
    },
  },
}

export default Shortcuts
