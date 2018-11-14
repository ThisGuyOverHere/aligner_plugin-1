import React, {Component} from 'react';
import PropTypes from "prop-types";

class SearchControlsComponent extends Component {
    static propTypes = {
        featuredSearchResult: PropTypes.number,
        searchResults: PropTypes.array,
        setFeatured: PropTypes.func,
    };

    constructor(props) {
        super(props);
        this.state = {
            featuredSearchResult: 0,
            searchResults: [],
        };
    }


    componentDidMount() {
    }

    componentWillUnmount() {

    }

    static getDerivedStateFromProps = (props, state) => {
        state.featuredSearchResult = props.featuredSearchResult;
        state.searchResults = props.searchResults;
        return state;
    };

    render() {
        return (
            <div id="controls">

            </div>
        );
    }


}

export default SearchControlsComponent;
