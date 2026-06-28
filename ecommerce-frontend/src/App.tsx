import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import Home from './pages/Home';
import PriceChecker from './pages/PriceChecker';
import Pesanan from './pages/Pesanan';
import Akun from './pages/Akun';
import { EcomProvider } from './context/EcomContext';
import { MainLayout } from './components/MainLayout';

function App() {
  return (
    <EcomProvider>
      <Router>
        <Routes>
          <Route element={<MainLayout />}>
            <Route path="/" element={<Home />} />
            <Route path="/cek-harga" element={<PriceChecker />} />
            <Route path="/pesanan" element={<Pesanan />} />
            <Route path="/akun" element={<Akun />} />
            {/* Future routes: /products, /cart, /login */}
          </Route>
        </Routes>
      </Router>
    </EcomProvider>
  );
}

export default App;
