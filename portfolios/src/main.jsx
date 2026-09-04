import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import './index.css';
import { PEOPLE } from './data.js';
import { Shell, useShell } from './lib/shell.jsx';

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

/**
 * Reads the person back out of the Shell rather than closing over the built
 * data, so an edit published from the admin area actually reaches the page.
 * Without this the Shell would merge the change and the page would carry on
 * rendering the copy it was handed at start-up.
 */
function Bound() {
  const { person } = useShell();

  return <Page person={person} />;
}

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <Shell person={{ ...person, defaultTheme: Page.defaultTheme || 'dark' }}>
      <Bound />
    </Shell>
  </StrictMode>
);
