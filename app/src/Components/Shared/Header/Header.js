import React, {Component} from 'react';
import {Link} from "react-router-dom";

class HeaderComponent extends Component {
    render() {
        return (
            <div>
                <Link to="/">Home</Link>
                <Link to="/project/123/123">Project</Link>
                <Link to="/not-found">Not Found</Link>
            </div>
        );
    }
}
export default HeaderComponent;