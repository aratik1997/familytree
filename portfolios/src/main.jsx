import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import './index.css';
import { PEOPLE } from './data.js';
import { Shell } from './lib/shell.jsx';

import Ansary from './people/ansary.jsx';
import Ashik from './people/ashik.jsx';
import Morsheda from './people/morsheda.jsx';
import Atik from './people/atik.jsx';
import Maria from './people/maria.jsx';
import Maimuna from './people/maimuna.jsx';
import Anas from './people/anas.jsx';
import Arafat from './people/arafat.jsx';

/**
 * Which of the eight this build is. Fixed at build time rather than routed,
 * because each one ships to its own subdomain — there is no second page for a
 * router to reach.
 */
const PAGES = { ansary: Ansary, ashik: Ashik, morsheda: Morsheda, atik: Atik,
                maria: Maria, maimuna: Maimuna, anas: Anas, arafat: Arafat };

const slug = __PERSON__;
const Page = PAGES[slug];
const person = PEOPLE[slug];

document.title = `${person.name.en} — ${person.profession.en}`;

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <Shell person={{ ...person, defaultTheme: Page.defaultTheme || 'dark' }}>
      <Page person={person} />
    </Shell>
  </StrictMode>
);
