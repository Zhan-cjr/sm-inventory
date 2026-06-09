import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import Home from './pages/Home';
import PriceChecker from './pages/PriceChecker';
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
            {/* Future routes: /products, /cart, /login */}
          </Route>
        </Routes>
      </Router>
    </EcomProvider>
  );
}

export default App;
