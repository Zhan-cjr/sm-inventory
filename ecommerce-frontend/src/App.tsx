import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import Home from './pages/Home';
import Navbar from './components/Navbar';
import Footer from './components/Footer';
import { EcomProvider } from './context/EcomContext';
import { CartDrawer } from './components/CartDrawer';
import { CheckoutModal } from './components/CheckoutModal';
import { BranchModal } from './components/BranchModal';
import MemberModal from './components/MemberModal';
import ProductDetailModal from './components/ProductDetailModal';

function App() {
  return (
    <EcomProvider>
      <Router>
        <div className="min-h-screen flex flex-col font-sans">
          <Navbar />
          <main className="flex-grow pt-36 md:pt-32"> {/* Dynamic offset for announcement + navbar */}
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
          <ProductDetailModal />
        </div>
      </Router>
    </EcomProvider>
  );
}

export default App;
