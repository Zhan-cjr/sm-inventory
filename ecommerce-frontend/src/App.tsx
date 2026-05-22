import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import Home from './pages/Home';
import Navbar from './components/Navbar';
import Footer from './components/Footer';
import { EcomProvider } from './context/EcomContext';
import { CartDrawer } from './components/CartDrawer';
import { CheckoutModal } from './components/CheckoutModal';
import { BranchModal } from './components/BranchModal';
import MemberModal from './components/MemberModal';

function App() {
  return (
    <EcomProvider>
      <Router>
        <div className="min-h-screen flex flex-col font-sans">
          <Navbar />
          <main className="flex-grow pt-24 md:pt-28"> {/* Dynamic offset for announcement + navbar */}
            <Routes>
              <Route path="/" element={<Home />} />
              {/* Future routes: /products, /cart, /login */}
            </Routes>
          </main>
          <Footer />
          
          {/* Global UI Elements */}
          <CartDrawer />
          <CheckoutModal />
          <BranchModal />
          <MemberModal />
        </div>
      </Router>
    </EcomProvider>
  );
}

export default App;
