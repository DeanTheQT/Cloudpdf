import React, { useState, useEffect } from 'react';
import axios from 'axios';
import './App.css';

axios.defaults.baseURL = 'http://127.0.0.1:8000';

function App() {
  const [user, setUser] = useState(null);
  const [showModal, setShowModal] = useState(false);
  const [authType, setAuthType] = useState('signin'); // signin, signup, upload
  const [formData, setFormData] = useState({ name: '', email: '', password: '' });
  const [file, setFile] = useState(null);
  const [message, setMessage] = useState('');
  const [allTheses, setAllTheses] = useState([]);
  const [searchQuery, setSearchQuery] = useState('');

  // Fetch current user and all theses
  useEffect(() => {
    const fetchUserAndTheses = async () => {
      try {
        await axios.get('/sanctum/csrf-cookie');
        const userRes = await axios.get('/api/user');
        if (userRes.data.user) setUser(userRes.data.user);

        const thesesRes = await axios.get('/api/theses'); // fetch all theses
        setAllTheses(thesesRes.data.theses || []);
      } catch (err) {
        console.error(err);
      }
    };
    fetchUserAndTheses();
  }, []);

  const handleChange = e => setFormData(prev => ({ ...prev, [e.target.name]: e.target.value }));

  const handleAuth = async () => {
    try {
      await axios.get('/sanctum/csrf-cookie');
      const url = authType === 'signin' ? '/api/login' : '/api/register';
      const res = await axios.post(url, formData);
      setUser(res.data.user);
      setShowModal(false);
      setMessage('');
    } catch (err) {
      setMessage(err.response?.data?.message || 'Error occurred');
    }
  };

  const handleLogout = async () => {
    try {
      await axios.get('/sanctum/csrf-cookie');
      await axios.post('/api/logout');
      setUser(null);
    } catch {}
  };

  const handleFileChange = e => setFile(e.target.files[0]);

  const handleUpload = async () => {
    if (!file) return alert('Select a PDF first.');

    const formDataObj = new FormData();
    formDataObj.append('pdf', file);

    try {
      const res = await axios.post('/api/upload', formDataObj, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });

      if (res.data?.thesis) {
        setAllTheses(prev => [res.data.thesis, ...prev]); // add new thesis to list
        setFile(null);
        setShowModal(false);
      } else {
        alert('Upload succeeded but no thesis data returned.');
      }
    } catch (err) {
      console.error(err.response?.data || err.message);
      alert(err.response?.data?.message || 'Upload failed.');
    }
  };

  // Simple search filtering
  const filteredTheses = allTheses.filter(thesis =>
    thesis.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
    thesis.summary.toLowerCase().includes(searchQuery.toLowerCase())
  );

  return (
    <div className="app-container">
      {/* Navbar */}
      <nav className="navbar">
        <h1>CloudPDF</h1>
        {user ? (
          <>
            <span>Welcome, {user.name}</span>
            <button onClick={() => { setAuthType('upload'); setShowModal(true); }}>Upload PDF</button>
            <button onClick={handleLogout}>Logout</button>
          </>
        ) : (
          <button onClick={() => { setAuthType('signin'); setShowModal(true); }}>Sign In / Sign Up</button>
        )}
      </nav>

      {/* Search bar */}
      {user && (
        <div className="search-container">
          <input
            type="text"
            className="search-input"
            placeholder="Search theses..."
            value={searchQuery}
            onChange={e => setSearchQuery(e.target.value)}
          />
        </div>
      )}

      {/* Modal for auth / upload */}
      {showModal && (
        <div className="modal-overlay">
          <div className="modal-content">
            {authType === 'upload' ? (
              <>
                <h3>Upload PDF</h3>
                <input type="file" accept="application/pdf" onChange={handleFileChange} />
                <button onClick={handleUpload}>Upload</button>
                <button onClick={() => setShowModal(false)}>Close</button>
              </>
            ) : (
              <>
                <h3>{authType === 'signin' ? 'Sign In' : 'Sign Up'}</h3>
                {authType === 'signup' && <input type="text" name="name" placeholder="Name" onChange={handleChange} />}
                <input type="email" name="email" placeholder="Email" onChange={handleChange} />
                <input type="password" name="password" placeholder="Password" onChange={handleChange} />
                <button onClick={handleAuth}>{authType === 'signin' ? 'Sign In' : 'Sign Up'}</button>
                {message && <p className="error-message">{message}</p>}
                <button onClick={() => setShowModal(false)}>Close</button>
                <button onClick={() => setAuthType(authType === 'signin' ? 'signup' : 'signin')}>
                  Switch to {authType === 'signin' ? 'Sign Up' : 'Sign In'}
                </button>
              </>
            )}
          </div>
        </div>
      )}

      <main className="main-content">
        {user ? <p>Logged in as {user.name} ({user.email})</p> : <p>Please sign in or sign up.</p>}

        {/* Display all theses */}
        <div className="thesis-results-container">
          {filteredTheses.map((thesis, idx) => (
            <div key={thesis.id} className="card">
            <h2>{thesis.title}</h2>
            <p>{thesis.summary}</p>
            <button
              onClick={async () => {
                try {
                  const res = await axios.get(
                    `http://127.0.0.1:8000/api/theses/download/${thesis.id}`,
                    { responseType: 'blob' } // important to handle binary file
                  );
                  const url = window.URL.createObjectURL(new Blob([res.data]));
                  const link = document.createElement('a');
                  link.href = url;
                  link.setAttribute('download', `${thesis.title}.pdf`);
                  document.body.appendChild(link);
                  link.click();
                  link.remove();
                } catch (err) {
                  alert('Download failed');
                  console.error(err);
                }
              }}
            >
              Download PDF
            </button>
          </div>

          ))}
        </div>
      </main>
    </div>
  );
}

export default App;
