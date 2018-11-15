import React, {Component} from 'react';
import PropTypes from "prop-types";

class SearchControlsComponent extends Component {
    static propTypes = {
        featuredSearchResult: PropTypes.number,
        occurrencesList: PropTypes.array,
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
        state.occurrencesList = props.occurrencesList;
        return state;
    };

    render() {
        return (
            <div id="controls">
                <button className={"increment ui button"} onClick={() => this.props.setFeatured(this.props.featuredSearchResult + 1)}>
                    +
                </button>
                <button className={"decrement ui button"} onClick={() => this.props.setFeatured(this.props.featuredSearchResult - 1)}>
                    -
                </button>
            </div>
        );
    }


}

export default SearchControlsComponent;
